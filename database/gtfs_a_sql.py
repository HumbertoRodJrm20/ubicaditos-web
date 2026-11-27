#!/usr/bin/env python3
"""Convierte un GTFS (.zip) en un script SQL para la base de datos de Ubicaditos.

Llena: routes, stops, route_stop. Compatible con PostgreSQL y SQLite >= 3.24.
No inventa autobuses, conductores ni viajes (el GTFS no los incluye).
Solo usa la biblioteca estandar de Python 3.8+.

Uso:
    python3 gtfs_a_sql.py mex-oax-oaxaca.zip -o oaxaca_gtfs.sql
    python3 gtfs_a_sql.py mex-oax-oaxaca.zip --por-sentido -o oaxaca_gtfs.sql
    python3 gtfs_a_sql.py mex-oax-oaxaca.zip --listar-operadoras
    python3 gtfs_a_sql.py mex-oax-oaxaca.zip --operadora tusug -o tusug.sql
"""
import argparse
import colorsys
import csv
import hashlib
import io
import json
import math
import re
import sys
import zipfile
from collections import defaultdict

MAX_VARCHAR = 255


def leer(zf, nombre, obligatorio=True):
    """Devuelve las filas de un archivo del GTFS como diccionarios."""
    ruta = next((n for n in zf.namelist() if n.split("/")[-1] == nombre), None)
    if ruta is None:
        if obligatorio:
            sys.exit(f"ERROR: el GTFS no contiene {nombre}")
        return []
    with zf.open(ruta) as f:
        texto = io.TextIOWrapper(f, encoding="utf-8-sig", newline="")
        return [
            {k.strip(): (v or "").strip() for k, v in fila.items() if k}
            for fila in csv.DictReader(texto)
        ]


def segundos(hora):
    if not hora:
        return None
    h, m, s = (int(x) for x in hora.split(":"))
    return h * 3600 + m * 60 + s


def distancia(a, b):
    lat1, lon1, lat2, lon2 = map(math.radians, (*a, *b))
    h = (math.sin((lat2 - lat1) / 2) ** 2
         + math.cos(lat1) * math.cos(lat2) * math.sin((lon2 - lon1) / 2) ** 2)
    return 2 * 6371000 * math.asin(math.sqrt(h))


def tiempos_acumulados(filas, coords):
    """Segundos desde el inicio del viaje por parada; interpola huecos por distancia."""
    t = []
    for f in filas:
        v = segundos(f.get("arrival_time"))
        t.append(v if v is not None else segundos(f.get("departure_time")))
    conocidos = [i for i, v in enumerate(t) if v is not None]
    if len(conocidos) < 2 or conocidos[0] != 0 or conocidos[-1] != len(t) - 1:
        return None
    dist = [0.0]
    for ant, act in zip(filas, filas[1:]):
        dist.append(dist[-1] + distancia(coords[ant["stop_id"]], coords[act["stop_id"]]))
    for i, j in zip(conocidos, conocidos[1:]):
        for k in range(i + 1, j):
            tramo = dist[j] - dist[i]
            p = (dist[k] - dist[i]) / tramo if tramo else (k - i) / (j - i)
            t[k] = t[i] + p * (t[j] - t[i])
    return [v - t[0] for v in t]


def sql(valor):
    if valor is None:
        return "NULL"
    if isinstance(valor, bool):
        return "TRUE" if valor else "FALSE"
    if isinstance(valor, (int, float)):
        return str(valor)
    return "'" + str(valor).replace("\x00", "").replace("'", "''") + "'"


def recortar(texto):
    return texto[:MAX_VARCHAR] if texto else None


def color_de(ruta):
    c = ruta.get("route_color", "").lstrip("#")
    if re.fullmatch(r"[0-9A-Fa-f]{6}", c) and c.upper() != "FFFFFF":
        return "#" + c.upper()
    # Sin color util en la fuente: color estable derivado del route_id.
    tono = int(hashlib.sha1(ruta["route_id"].encode()).hexdigest()[:6], 16) / 0xFFFFFF
    r, g, b = colorsys.hls_to_rgb(tono, 0.45, 0.75)
    return "#%02X%02X%02X" % (int(r * 255), int(g * 255), int(b * 255))


def construir(zf, args):
    operadoras = {a.get("agency_id", ""): a.get("agency_name", "") for a in leer(zf, "agency.txt")}
    rutas = leer(zf, "routes.txt")
    # Si solo hay una operadora, routes.agency_id puede omitirse.
    unica = next(iter(operadoras.values())) if len(operadoras) == 1 else ""

    def operadora(r):
        return operadoras.get(r.get("agency_id", ""), unica)

    if args.operadora:
        rutas = [r for r in rutas if args.operadora.lower() in operadora(r).lower()]
        if not rutas:
            sys.exit(f"ERROR: ninguna ruta de la operadora '{args.operadora}'")

    paradas = {}
    coords = {}
    for s in leer(zf, "stops.txt"):
        try:
            lat, lng = float(s["stop_lat"]), float(s["stop_lon"])
        except (KeyError, ValueError):
            continue
        if not (-90 <= lat <= 90 and -180 <= lng <= 180) or (lat == 0 and lng == 0):
            continue
        paradas[s["stop_id"]] = s
        coords[s["stop_id"]] = (lat, lng)

    viajes_por_ruta = defaultdict(list)
    for v in leer(zf, "trips.txt"):
        viajes_por_ruta[v["route_id"]].append(v)

    horarios = defaultdict(list)
    for f in leer(zf, "stop_times.txt"):
        if f["stop_id"] in paradas:
            horarios[f["trip_id"]].append(f)
    for filas in horarios.values():
        filas.sort(key=lambda f: int(f["stop_sequence"]))

    trazos = defaultdict(list)
    for p in leer(zf, "shapes.txt", obligatorio=False):
        trazos[p["shape_id"]].append(
            (int(p["shape_pt_sequence"]), float(p["shape_pt_lat"]), float(p["shape_pt_lon"])))
    for pts in trazos.values():
        pts.sort()

    stops_sql = {}      # clave -> (nombre, direccion, lat, lng, descripcion)
    rutas_sql = []
    codigos = set()
    omitidas = []

    for r in sorted(rutas, key=lambda x: x["route_id"]):
        # Viaje representativo por sentido: el que tiene mas paradas.
        mejores = {}
        for v in sorted(viajes_por_ruta[r["route_id"]], key=lambda x: x["trip_id"]):
            n = len(horarios.get(v["trip_id"], []))
            d = v.get("direction_id", "")
            if n >= 2 and (d not in mejores or n > len(horarios[mejores[d]["trip_id"]])):
                mejores[d] = v
        if not mejores:
            omitidas.append(r["route_id"])
            continue

        sentidos = sorted(mejores)
        grupos = [[d] for d in sentidos] if args.por_sentido else [sentidos]

        for grupo in grupos:
            sufijo = f"-S{grupo[0] or '0'}" if args.por_sentido and len(sentidos) > 1 else ""
            base = args.prefijo + re.sub(r"[^A-Za-z0-9_.-]+", "-", r["route_id"]) + sufijo
            codigo, n = base[:MAX_VARCHAR], 2
            while codigo in codigos:
                codigo, n = f"{base[:MAX_VARCHAR - 4]}~{n}", n + 1
            codigos.add(codigo)

            secuencia, vistas, trazo = [], set(), []
            desfase, con_tiempos = 0, True
            for d in grupo:
                viaje = mejores[d]
                filas = horarios[viaje["trip_id"]]
                acum = tiempos_acumulados(filas, coords)
                con_tiempos = con_tiempos and acum is not None
                for i, f in enumerate(filas):
                    s = paradas[f["stop_id"]]
                    lat, lng = (round(x, 7) for x in coords[f["stop_id"]])
                    nombre = recortar(s.get("stop_name")) or f"Parada {f['stop_id']}"[:MAX_VARCHAR]
                    clave = (nombre, lat, lng)
                    stops_sql.setdefault(clave, (
                        nombre, recortar(s.get("stop_desc")), lat, lng,
                        f"Fuente GTFS, stop_id: {f['stop_id']}"))
                    # route_stop es unico por (route_id, stop_id): se conserva la primera visita.
                    if clave not in vistas:
                        vistas.add(clave)
                        secuencia.append((clave, desfase + acum[i] if con_tiempos else None))
                if con_tiempos:
                    desfase += acum[-1]
                for _, lat, lng in trazos.get(viaje.get("shape_id", ""), []):
                    punto = {"lat": round(lat, 6), "lng": round(lng, 6)}
                    if not trazo or trazo[-1] != punto:
                        trazo.append(punto)

            enlaces, previo = [], 0
            for orden, (clave, t) in enumerate(secuencia, 1):
                minutos = None
                if con_tiempos:
                    actual = round(t / 60)
                    minutos, previo = actual - previo, actual
                enlaces.append((clave, orden, minutos))

            nombre = r.get("route_long_name") or r.get("route_short_name") or r["route_id"]
            if sufijo:
                destino = mejores[grupo[0]].get("trip_headsign")
                nombre += f" (hacia {destino})" if destino else f" (sentido {grupo[0]})"
            descripcion = (f"Operadora: {operadora(r) or 'sin dato'}. "
                           f"Ruta {r.get('route_short_name') or r['route_id']}. "
                           f"Fuente: GTFS, route_id {r['route_id']}.")
            rutas_sql.append({
                "code": codigo,
                "name": recortar(nombre),
                "description": descripcion,
                "color": color_de(r),
                "duration": round(desfase / 60) if con_tiempos and desfase > 0 else None,
                "path": json.dumps(trazo, separators=(",", ":")) if len(trazo) > 1 else None,
                "stops": enlaces,
            })

    return rutas_sql, stops_sql, omitidas


def escribir(salida, rutas, paradas):
    w = salida.write
    w("-- Generado por gtfs_a_sql.py. Requiere las migraciones ya aplicadas.\n")
    w("-- Se puede ejecutar varias veces: actualiza rutas por 'code' y no duplica paradas.\n")
    w("BEGIN;\n\n-- Paradas\n")
    for nombre, direccion, lat, lng, desc in paradas.values():
        w("INSERT INTO stops (name, address, latitude, longitude, description, is_active, "
          "created_at, updated_at)\n"
          f"SELECT {sql(nombre)}, {sql(direccion)}, {lat:.7f}, {lng:.7f}, {sql(desc)}, TRUE, "
          "CURRENT_TIMESTAMP, CURRENT_TIMESTAMP\n"
          f"WHERE NOT EXISTS (SELECT 1 FROM stops WHERE name = {sql(nombre)} "
          f"AND latitude = {lat:.7f} AND longitude = {lng:.7f});\n")
    w("\n-- Rutas y sus paradas\n")
    for r in rutas:
        w("INSERT INTO routes (name, code, description, color, estimated_duration, "
          "path_coordinates, is_active, created_at, updated_at)\n"
          f"VALUES ({sql(r['name'])}, {sql(r['code'])}, {sql(r['description'])}, "
          f"{sql(r['color'])}, {sql(r['duration'])}, {sql(r['path'])}, TRUE, "
          "CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)\n"
          "ON CONFLICT (code) DO UPDATE SET name = excluded.name, "
          "description = excluded.description, color = excluded.color, "
          "estimated_duration = excluded.estimated_duration, "
          "path_coordinates = excluded.path_coordinates, is_active = excluded.is_active, "
          "updated_at = CURRENT_TIMESTAMP;\n")
        id_ruta = f"(SELECT id FROM routes WHERE code = {sql(r['code'])})"
        # Solo se reemplazan las paradas de las rutas importadas por este script.
        w(f"DELETE FROM route_stop WHERE route_id = {id_ruta};\n")
        for (nombre, lat, lng), orden, minutos in r["stops"]:
            w('INSERT INTO route_stop (route_id, stop_id, "order", estimated_time_from_previous, '
              "created_at, updated_at)\n"
              f"VALUES ({id_ruta}, (SELECT MIN(id) FROM stops WHERE name = {sql(nombre)} "
              f"AND latitude = {lat:.7f} AND longitude = {lng:.7f}), {orden}, {sql(minutos)}, "
              "CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);\n")
        w("\n")
    w("COMMIT;\n")


def main():
    ap = argparse.ArgumentParser(description="GTFS a SQL para Ubicaditos")
    ap.add_argument("gtfs", help="archivo .zip con el GTFS")
    ap.add_argument("-o", "--salida", default="oaxaca_gtfs.sql")
    ap.add_argument("--prefijo", default="GT-", help="prefijo de routes.code (evita choques con OAX-*)")
    ap.add_argument("--operadora", help="importar solo operadoras cuyo nombre contenga este texto")
    ap.add_argument("--por-sentido", action="store_true",
                    help="una ruta por sentido (por defecto: ida y vuelta unidas)")
    ap.add_argument("--listar-operadoras", action="store_true")
    args = ap.parse_args()

    with zipfile.ZipFile(args.gtfs) as zf:
        if args.listar_operadoras:
            rutas = leer(zf, "routes.txt")
            for a in leer(zf, "agency.txt"):
                n = sum(r.get("agency_id", "") == a.get("agency_id", "") for r in rutas)
                print(f"{n:4d} rutas  {a.get('agency_name')}")
            return
        rutas, paradas, omitidas = construir(zf, args)

    if not rutas:
        sys.exit("ERROR: no se obtuvo ninguna ruta con al menos 2 paradas")
    with open(args.salida, "w", encoding="utf-8", newline="\n") as f:
        escribir(f, rutas, paradas)

    print(f"Archivo: {args.salida}")
    print(f"Rutas: {len(rutas)} | Paradas: {len(paradas)} | "
          f"Relaciones route_stop: {sum(len(r['stops']) for r in rutas)}")
    print(f"Sin trazo: {sum(r['path'] is None for r in rutas)} | "
          f"Sin duracion: {sum(r['duration'] is None for r in rutas)}")
    if omitidas:
        print(f"Omitidas por no tener 2+ paradas: {', '.join(omitidas)}")


if __name__ == "__main__":
    main()
