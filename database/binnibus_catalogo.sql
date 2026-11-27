-- Catalogo de 36 rutas BinniBus listadas en https://rutasoaxaca.com (consultado 2026-11-26).
-- La pagina no entrego paradas, coordenadas ni trazos: se insertan INACTIVAS (is_active = FALSE).
-- RC-11: el nombre en zapoteco venia con caracteres corruptos y se omitio.
-- Compatible con PostgreSQL y SQLite. Si el code ya existe, no se modifica.
BEGIN;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Jnón: Esquipulas - Central de Abastos', 'BB-RA-01', 'BinniBus RA-01. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Mo'' (Montaña): Santa María Atzompa - Guadalupe Victoria', 'BB-RA-02', 'BinniBus RA-02. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Ladxidó (Corazón): Yuroo Parque del Amor - De Azucena, Camino a Huayápam', 'BB-RA-03', 'BinniBus RA-03. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Naa (Mamá): Monumento a la Madre / Tecnológico - Santa Cruz Amilpas', 'BB-RA-04', 'BinniBus RA-04. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Guie'' (Flor): Colonia Jardín - Anáhuac', 'BB-RA-05', 'BinniBus RA-05. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Wagul (Música): San Martín Mexicapam - Primera Etapa', 'BB-RA-06', 'BinniBus RA-06. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Uxacha: 2da. Sección Guadalupe Victoria - Colonia Moctezuma', 'BB-RA-07', 'BinniBus RA-07. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Koo (Luna): Agencia Donají - Colonia Satélite', 'BB-RA-08', 'BinniBus RA-08. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Moog (Casa Tradicional): Fraccionamiento Los Álamos - Panteón Jardín', 'BB-RA-09', 'BinniBus RA-09. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Tza'' Tøk (Cueva): La Cuevita - Fracc. El Rosario 5ª Etapa', 'BB-RA-10', 'BinniBus RA-10. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Tüchi: Montoya - Colonia Jardín', 'BB-RA-11', 'BinniBus RA-11. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Najmé (Maíz): Colonia La Soledad - Primera Etapa', 'BB-RA-12', 'BinniBus RA-12. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Waxin: Yuroo Parque del Amor Ladxido'' - San Luis Beltrán', 'BB-RA-13', 'BinniBus RA-13. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Poj: Montoya - Fraccionamiento El Rosario', 'BB-RA-14', 'BinniBus RA-14. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Xra'': Colonia Monte Albán - Cd. Administrativa', 'BB-RA-15', 'BinniBus RA-15. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Ri''nîn: Colonia Guelaguetza - Plaza del Valle', 'BB-RA-16', 'BinniBus RA-16. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Bakku Nunni: Monte Albán - Símbolos Patrios', 'BB-RA-17', 'BinniBus RA-17. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Nave''e: Yuroo Viguera - Centro de Rehabilitación DIF', 'BB-RA-18', 'BinniBus RA-18. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Nandá (Agua): Yuroo Viguera - Huitzo', 'BB-RA-19', 'BinniBus RA-19. Fuente: rutasoaxaca.com', '#2563EB', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Dugue (Joya): Pueblo Nuevo "La Joya" - Agencia Donají', 'BB-RC-01', 'BinniBus RC-01. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Fane Kantsini (Tres Colibríes): Viguera - Primera Etapa', 'BB-RC-02', 'BinniBus RC-02. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Nisa (Cántaro): San Felipe del Agua - Plaza del Valle', 'BB-RC-03', 'BinniBus RC-03. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Atlantekomatl (Concha de Mar): Fracc. Benito Juárez / Esmeralda - Plaza del Valle', 'BB-RC-04', 'BinniBus RC-04. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Xuujx (Instrumento de viento): San Pablo Etla - Oaxaca Centro - Plaza de la Danza', 'BB-RC-05', 'BinniBus RC-05. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Mondok (Pescadores): San Jacinto Amilpas - Camino a Huayápam', 'BB-RC-06', 'BinniBus RC-06. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Vitú Vií (Alebrije): Colonia Monte Bello - Monumento a Juárez, Santa Cruz Xoxocotlán', 'BB-RC-07', 'BinniBus RC-07. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Ruja (Diablo): Colonia Jardín - Colonia Heladio Ramírez López', 'BB-RC-08', 'BinniBus RC-08. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Kítsian Jan: Colonia Bugambilias - Fracc. El Rosario', 'BB-RC-09', 'BinniBus RC-09. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Kaymo: Manzana L - Ánimas Trujano', 'BB-RC-10', 'BinniBus RC-10. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('La Raya Aeropuerto - Módulo Azul', 'BB-RC-11', 'BinniBus RC-11. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Ñunda: Tlalixtac de Cabrera - Yuroo Parque del Amor ''Ladxido''', 'BB-RC-12', 'BinniBus RC-12. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Yuku Tay (Cerro Sagrado): Hospital ISSSTE - Base CRIT', 'BB-RC-13', 'BinniBus RC-13. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Laba'': Yuroo Viguera - San Sebastián Tutla', 'BB-RC-14', 'BinniBus RC-14. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Yu Ngnta'': H. de la Mujer y la Niñez Oaxaqueña - Base Walmart Símbolos Patrios - Módulo Azul', 'BB-RC-15', 'BinniBus RC-15. Fuente: rutasoaxaca.com', '#16A34A', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Kayaal (Florecer): Yuroo Viguera - Yuroo Xoxocotlán', 'BB-RT-01', 'BinniBus RT-01. Fuente: rutasoaxaca.com', '#DC2626', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
INSERT INTO routes (name, code, description, color, is_active, created_at, updated_at)
VALUES ('Yii Gap: Yuroo Viguera - Ciudad Administrativa', 'BB-RT-02', 'BinniBus RT-02. Fuente: rutasoaxaca.com', '#DC2626', FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON CONFLICT (code) DO NOTHING;
COMMIT;
