# TODO — kosmetika a doladění

Drobnosti, které nespadají do právě běžící etapy. Rozdělené podle toho, kam patří.
Toku / dat / URL se dotýkající věci sem NEpatří — ty se řeší hned.

## Frontend — vzhled a texty

- [ ] `home.title` / `<title>` na homepage zní divně: „Trička s potiskem — Trička s potiskem"
      (klíč `home.title` == `store.name`). Dát homepage vlastní tagline.
- [ ] `home.seo_h`: „Trička s potiskem od %store%" → „…od Trička s potiskem". Přeformulovat.
- [ ] Hero „vějíř" triček na homepage je velký a plochý (SVG placeholdery) — po napojení
      reálných obrázků přehodnotit.
- [ ] Detail produktu: nadpis „Popis" a hned pod ním „Gramáž / Materiál" — když je
      `descriptionHtml` prázdný, sekce působí uříznutě. Skrýt nadpis, když není obsah.
- [ ] Filtry v kategorii (pohlaví, barva, velikost, cena) — v E4 dočasně vypnuté,
      dodělat pořádně (fasety z atributů).
- [ ] Parent kategorie (`vypis_zbozi_parent_simple`, např. „Narozeninová trička") mají
      málo/žádné vlastní produkty — renderovat spíš jako rozcestník podkategorií.
- [ ] Pluralizace: „1 produktů" → „1 produkt" / „2 produkty" / „5 produktů"
      (Symfony ICU plurals v překladech).
- [ ] Produktové obrázky se DOČASNĚ tahají z ostrých webů
      (`PRODUCT_IMAGE_HOST_PREFIX="https://www."` v `.env`). Po stažení ~15 GB
      lokálně nastavit prázdné a servírovat z `/pictures/`. Část cest na ostré
      verzi 404 (produkt se změnil) — u těch se dlaždice nechá neutrální.

## Obsah / číselníky (patří do obsahového/SEO workstreamu)

- [ ] „Technologie potisku: 2" — legacy číselník `technologie` nenamapovaný na text
      (digitální tisk / termolis / sítotisk / DTF).
- [ ] Šablonové artefakty v produktových textech: „…barva 12 ve složení 85 % bavlna…",
      generické popisy s proměnnými. → 1:1 migrace teď, systematické čištění později.
- [ ] `~9 800` nejnovějších `8_shopdata_podrobnosti_rozsirene` řádků chybí (export padal u id 458076) —
      až bude re-export, doplnit popisky per varianta (`descriptions` step). Nejnovější
      produkty (id varianty > 458076) teď nemají hlavní popis vůbec.
- [ ] `parameter_templates` (Materiál, Gramáž…) jsou naimportované jen pro tsp
      (`8_shopdata_list` je jen v tsp dumpu). tsl/cd nastavit ručně v `app:seed`
      nebo dodat dumpy `8_shopdata_list` / `_faq` pro tsl.
- [ ] FAQ „Pro koho je tričko určené?" má odpověď == parametr „Určeno jako" —
      zvážit generovat dynamicky místo ukládat (30k řádků navíc).

## Pokladna / objednávky (E5 dluhy)

- [ ] Při volbě dobírky se cena dopravy na stránce nepřepočítá (zobrazí „převodem");
      finální total je server-side správně. Dořešit malým fetchem nebo přepočtem v JS.
- [ ] Výběr Zásilkovna/Packeta výdejního místa = zatím textové pole. Napojit widget.
- [ ] Slevové kódy — zatím žádné.
- [ ] Faktury se negenerují (`InvoiceSeries` je naseeded). → E7.

## Infrastruktura / DX

- [ ] `#[ORM\OrderBy(['x' => 'ASC'])]` v entitách — deprecated v Doctrine, přejít na
      `Order` enum (6 míst).
- [ ] `compose.override.yaml` má z recepta `symfony/mailer` navíc mailpit službu —
      DDEV ji ignoruje (má vlastní), ale je to matoucí. Zvážit smazání bloku.
- [ ] DDEV web kontejner občas spadne na stale apache pidfile (WSL). Zdokumentovat /
      najít trvalé řešení.
