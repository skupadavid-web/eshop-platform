# Eshop platforma

Multi-tenant e-commerce platforma pro trička a dresy s potiskem. Nahrazuje 3 stávající eshopy
(trickaspotiskem.eu, trickaspotlacou.eu, cooldresy.cz) z jedné kódové základny.

Analýza a rozhodnutí: `C:\eshop_novy\analyza\` (mimo tento repozitář).

## Stack

PHP 8.3 · Symfony 7.4 LTS · MariaDB 10.11 · Redis · Apache · DDEV (lokálně) / Ansible (VPS).

## Lokální běh

```bash
ddev start
```

| URL | |
|---|---|
| https://eshop-platform.ddev.site | výchozí (fallback eshop) |
| https://trickaspotiskem.ddev.site | eshop TSP (cs) |
| https://trickaspotlacou.ddev.site | eshop TSL (sk) |
| https://cooldresy.ddev.site | eshop CD (cs) |
| https://eshop-platform.ddev.site:8026 | Mailpit (odchycené e-maily) |

Aktivní eshop se určuje podle hostname požadavku — viz `src/Store/`.

## Příkazy

```bash
make help      # seznam
make ci        # cs-fixer + phpstan + phpunit (jako v CI)
make console c="cache:clear"
```

## Struktura

```
src/Store/               multi-tenant jádro (Store, StoreRegistry, StoreContext)
src/EventSubscriber/     StoreResolverSubscriber – volba eshopu podle domény
src/Controller/          kontrolery
templates/               Twig šablony (později per-eshop témata v themes/)
```

## Konvence

Kód a commity anglicky, dokumentace česky, UI CZ/SK. Viz `CLAUDE.md`.
