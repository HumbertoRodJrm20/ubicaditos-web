-- Autobuses FICTICIOS de prueba: 2 en cada una de 3 rutas (6 en total).
-- Toma las 3 primeras rutas GT- activas (por code) con 4 o mas paradas.
-- Para elegir otras, reemplaza cada subconsulta por (SELECT id FROM routes WHERE code = 'GT-xxx').
-- Asigna conductores activos sin autobus, si existen; si no, driver_id queda NULL.
-- Requiere que las rutas ya esten cargadas. Compatible con PostgreSQL y SQLite.
BEGIN;

INSERT INTO buses (license_plate, model, capacity, status, current_latitude, current_longitude,
    last_location_update, driver_id, created_at, updated_at)
SELECT 'PRB001', 'Unidad de prueba (ficticia)', 40, 'active', s.latitude, s.longitude, CURRENT_TIMESTAMP,
    (SELECT u.id FROM users u WHERE u.role = 'driver' AND u.is_active
        AND NOT EXISTS (SELECT 1 FROM buses b WHERE b.driver_id = u.id) ORDER BY u.id LIMIT 1),
    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM route_stop rs JOIN stops s ON s.id = rs.stop_id
WHERE rs.route_id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 0)
ORDER BY rs."order" LIMIT 1 OFFSET 0
ON CONFLICT (license_plate) DO NOTHING;

INSERT INTO bus_route (bus_id, route_id, assigned_at, created_at, updated_at)
SELECT b.id, r.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM buses b, routes r
WHERE b.license_plate = 'PRB001' AND r.id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 0)
    AND NOT EXISTS (SELECT 1 FROM bus_route br WHERE br.bus_id = b.id);

INSERT INTO buses (license_plate, model, capacity, status, current_latitude, current_longitude,
    last_location_update, driver_id, created_at, updated_at)
SELECT 'PRB002', 'Unidad de prueba (ficticia)', 40, 'active', s.latitude, s.longitude, CURRENT_TIMESTAMP,
    (SELECT u.id FROM users u WHERE u.role = 'driver' AND u.is_active
        AND NOT EXISTS (SELECT 1 FROM buses b WHERE b.driver_id = u.id) ORDER BY u.id LIMIT 1),
    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM route_stop rs JOIN stops s ON s.id = rs.stop_id
WHERE rs.route_id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 0)
ORDER BY rs."order" LIMIT 1 OFFSET 3
ON CONFLICT (license_plate) DO NOTHING;

INSERT INTO bus_route (bus_id, route_id, assigned_at, created_at, updated_at)
SELECT b.id, r.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM buses b, routes r
WHERE b.license_plate = 'PRB002' AND r.id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 0)
    AND NOT EXISTS (SELECT 1 FROM bus_route br WHERE br.bus_id = b.id);

INSERT INTO buses (license_plate, model, capacity, status, current_latitude, current_longitude,
    last_location_update, driver_id, created_at, updated_at)
SELECT 'PRB003', 'Unidad de prueba (ficticia)', 40, 'active', s.latitude, s.longitude, CURRENT_TIMESTAMP,
    (SELECT u.id FROM users u WHERE u.role = 'driver' AND u.is_active
        AND NOT EXISTS (SELECT 1 FROM buses b WHERE b.driver_id = u.id) ORDER BY u.id LIMIT 1),
    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM route_stop rs JOIN stops s ON s.id = rs.stop_id
WHERE rs.route_id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 1)
ORDER BY rs."order" LIMIT 1 OFFSET 0
ON CONFLICT (license_plate) DO NOTHING;

INSERT INTO bus_route (bus_id, route_id, assigned_at, created_at, updated_at)
SELECT b.id, r.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM buses b, routes r
WHERE b.license_plate = 'PRB003' AND r.id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 1)
    AND NOT EXISTS (SELECT 1 FROM bus_route br WHERE br.bus_id = b.id);

INSERT INTO buses (license_plate, model, capacity, status, current_latitude, current_longitude,
    last_location_update, driver_id, created_at, updated_at)
SELECT 'PRB004', 'Unidad de prueba (ficticia)', 40, 'active', s.latitude, s.longitude, CURRENT_TIMESTAMP,
    (SELECT u.id FROM users u WHERE u.role = 'driver' AND u.is_active
        AND NOT EXISTS (SELECT 1 FROM buses b WHERE b.driver_id = u.id) ORDER BY u.id LIMIT 1),
    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM route_stop rs JOIN stops s ON s.id = rs.stop_id
WHERE rs.route_id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 1)
ORDER BY rs."order" LIMIT 1 OFFSET 3
ON CONFLICT (license_plate) DO NOTHING;

INSERT INTO bus_route (bus_id, route_id, assigned_at, created_at, updated_at)
SELECT b.id, r.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM buses b, routes r
WHERE b.license_plate = 'PRB004' AND r.id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 1)
    AND NOT EXISTS (SELECT 1 FROM bus_route br WHERE br.bus_id = b.id);

INSERT INTO buses (license_plate, model, capacity, status, current_latitude, current_longitude,
    last_location_update, driver_id, created_at, updated_at)
SELECT 'PRB005', 'Unidad de prueba (ficticia)', 40, 'active', s.latitude, s.longitude, CURRENT_TIMESTAMP,
    (SELECT u.id FROM users u WHERE u.role = 'driver' AND u.is_active
        AND NOT EXISTS (SELECT 1 FROM buses b WHERE b.driver_id = u.id) ORDER BY u.id LIMIT 1),
    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM route_stop rs JOIN stops s ON s.id = rs.stop_id
WHERE rs.route_id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 2)
ORDER BY rs."order" LIMIT 1 OFFSET 0
ON CONFLICT (license_plate) DO NOTHING;

INSERT INTO bus_route (bus_id, route_id, assigned_at, created_at, updated_at)
SELECT b.id, r.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM buses b, routes r
WHERE b.license_plate = 'PRB005' AND r.id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 2)
    AND NOT EXISTS (SELECT 1 FROM bus_route br WHERE br.bus_id = b.id);

INSERT INTO buses (license_plate, model, capacity, status, current_latitude, current_longitude,
    last_location_update, driver_id, created_at, updated_at)
SELECT 'PRB006', 'Unidad de prueba (ficticia)', 40, 'active', s.latitude, s.longitude, CURRENT_TIMESTAMP,
    (SELECT u.id FROM users u WHERE u.role = 'driver' AND u.is_active
        AND NOT EXISTS (SELECT 1 FROM buses b WHERE b.driver_id = u.id) ORDER BY u.id LIMIT 1),
    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM route_stop rs JOIN stops s ON s.id = rs.stop_id
WHERE rs.route_id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 2)
ORDER BY rs."order" LIMIT 1 OFFSET 3
ON CONFLICT (license_plate) DO NOTHING;

INSERT INTO bus_route (bus_id, route_id, assigned_at, created_at, updated_at)
SELECT b.id, r.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM buses b, routes r
WHERE b.license_plate = 'PRB006' AND r.id = (SELECT r.id FROM routes r WHERE r.is_active AND r.code LIKE 'GT-%'
    AND (SELECT COUNT(*) FROM route_stop x WHERE x.route_id = r.id) >= 4
    ORDER BY r.code LIMIT 1 OFFSET 2)
    AND NOT EXISTS (SELECT 1 FROM bus_route br WHERE br.bus_id = b.id);

COMMIT;

-- Verificacion: debe mostrar 6 filas.
SELECT b.license_plate, r.code, r.name, b.driver_id
FROM buses b JOIN bus_route br ON br.bus_id = b.id JOIN routes r ON r.id = br.route_id
WHERE b.license_plate LIKE 'PRB%' ORDER BY b.license_plate;
