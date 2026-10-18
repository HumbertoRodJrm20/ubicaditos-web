"""
Fixtures de pytest para las pruebas E2E de Ubicaditos.

El navegador (Chrome headless) se crea una sola vez por corrida y se cierra
automáticamente al final. Las pruebas que necesitan otro viewport usan
``driver_factory``.
"""

import pytest

from config import VIEWPORTS, build_driver


@pytest.fixture(scope="session")
def driver():
    """Navegador de escritorio compartido por toda la corrida."""
    d = build_driver(VIEWPORTS["desktop"])
    yield d
    d.quit()


@pytest.fixture
def driver_factory():
    """Fábrica de navegadores adicionales (p. ej. la suite responsive)."""
    created = []

    def _build(viewport=None):
        d = build_driver(viewport or VIEWPORTS["desktop"])
        created.append(d)
        return d

    yield _build

    for d in created:
        d.quit()
