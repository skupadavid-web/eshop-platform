# CLAUDE.md — konvence projektu

## Co to je
Multi-tenant e-commerce platforma (Symfony 7.4 LTS / PHP 8.3 / MariaDB 10.11 / Redis).
Jedna kódová základna → 3 eshopy: trickaspotiskem.eu (cs), trickaspotlacou.eu (sk), cooldresy.cz (cs).
Analytické podklady a rozhodnutí jsou v `C:\eshop_novy\analyza\` (mimo repo).

## Jazyk
- Kód, identifikátory, komentáře, commit messages: **anglicky**
- Dokumentace (README, docs): **česky**
- UI texty: **cs / sk** přes Symfony Translation

## Multi-tenancy
- Aktivní eshop = `App\Store\StoreContext` (request-scoped), plní `StoreResolverSubscriber` podle hostname.
- Katalog eshopů je zatím v `App\Store\StoreRegistry` (kód); později tabulka `stores`.
- Tenant-scoped entity budou mít `store` / `store_id`. Sdílené: produktový master, zákazník-identita,
  centrální evidence objednávek.

## Prostředí
- Lokálně **DDEV** (`ddev start`), ne nativní PHP. Příkazy přes `ddev exec` / `make`.
- Verze zapíchnuté v `.ddev/config.yaml` musí odpovídat produkčnímu Ansible playbooku.
- Kód žije ve WSL2 (`~/code/eshop-platform`), ne na `/mnt/c`.

## Kvalita
- `make ci` = php-cs-fixer (@Symfony + strict_types) + PHPStan level 6 + PHPUnit. Musí projít před commitem.
- Nové třídy: `declare(strict_types=1)`, `final` kde to jde, konstruktor property promotion.

## Co NErobit
- Nepřebírat kód ze starého systému (`eshop_old`) — jen doménovou logiku a URL strukturu.
- Nepsat tajné údaje do repa (DB hesla, API klíče) — `.env.local` / secrets.
