"""
Verificación responsive de las vistas clave en móvil (375×667) y escritorio
(1366×768): ninguna página debe generar scroll horizontal (se toleran 4 px por
redondeos de subpíxel).
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

from config import USERS, VIEWPORTS, goto, horizontal_overflow, login, pause

TOLERANCE = 4

PAGES = [
    {"path": "/passenger", "role": "passenger", "label": "Mapa del pasajero"},
    {"path": "/passenger/buses", "role": "passenger", "label": "Autobuses del pasajero"},
    {"path": "/passenger/routes", "role": "passenger", "label": "Rutas del pasajero"},
    {"path": "/driver", "role": "driver", "label": "Panel del conductor"},
    {"path": "/driver/statistics", "role": "driver", "label": "Estadísticas del conductor"},
    {"path": "/admin", "role": "admin", "label": "Panel de administración"},
    {"path": "/admin/emergencies", "role": "admin", "label": "Incidencias (admin)"},
    {"path": "/admin/buses", "role": "admin", "label": "Autobuses (admin)"},
    {"path": "/admin/routes", "role": "admin", "label": "Rutas (admin)"},
    {"path": "/profile", "role": "admin", "label": "Perfil de usuario"},
]


def run_viewport_suite(driver, viewport):
    current_role = None

    for page in PAGES:
        if current_role != page["role"]:
            login(driver, USERS[page["role"]])
            current_role = page["role"]

        goto(driver, page["path"])
        WebDriverWait(driver, 15).until(EC.element_to_be_located((By.TAG_NAME, "body")))
        # Pequeña pausa para que Leaflet/Alpine terminen de componer el layout.
        pause(0.8)

        overflow = horizontal_overflow(driver)
        assert overflow <= TOLERANCE, (
            f"{page['label']} ({page['path']}) desborda {overflow}px "
            f"en {viewport['width']}px de ancho"
        )


def test_las_vistas_clave_no_desbordan_horizontalmente_en_movil(driver_factory):
    viewport = VIEWPORTS["mobile"]
    run_viewport_suite(driver_factory(viewport), viewport)


def test_las_vistas_clave_no_desbordan_horizontalmente_en_escritorio(driver_factory):
    viewport = VIEWPORTS["desktop"]
    run_viewport_suite(driver_factory(viewport), viewport)
