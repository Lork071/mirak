# Test Plan & Test Cases

**Projekt:** Webový registrační formulář (Vue 3 + PrimeVue / PHP backend)

**Rozsah:** Frontend validace + UI flow, API kontrakty a odpovědi, business logika (cena, volby programu, ubytování, jídlo/workshopy), e2e scénáře, negativní testy, hranice a regrese.

**Poznámky k implementaci:**

* Frontend volá `api_post(config.endpoint_ticket|endpoint_login, { method: 'METODA', parameters: {...} })`.
* Důležité flagy: `route.query.only_friday`, `route.query.meal` → ovlivňují `static_config` a výchozí cenu.
* Viditelnost formulářových částí je vázána na `EmailVerified` a `formVal.no_pii`.
* Rozdíly FE/BE validací jsou uvedeny v sekci „Neshody validací FE vs BE“ – testovat!

---

## 1) Předpoklady a testovací data

### 1.1 Testovací uživatelé/e‑maily

* **[verif\_ok@example.com](mailto:verif_ok@example.com)** – existuje v DB\_emails, `verify = true`.
* **[need\_otp@example.com](mailto:need_otp@example.com)** – existuje v DB\_emails, `verify = false`, backend generuje a posílá OTP.
* **[new\_user@example.com](mailto:new_user@example.com)** – v DB\_emails zatím chybí; backend vloží řádek a pošle OTP.
* **[dup\_user@example.com](mailto:dup_user@example.com)** – existuje v DB\_event se stejnou kombinací email+first\_name+last\_name (duplicitní registrace).

### 1.2 OTP kódy

* **Platný:** přesně 6 číslic odpovídající hodnotě v DB (`otp`).
* **Neplatný:** 6 číslic odlišných od DB.
* **Hraniční:** méně než 6 znaků / více než 6 / ne‑číselné znaky.

### 1.3 Seznam zemí

* `CountryService.getCountries()` vrací např. `[{name:"Czech Republic", code:"CZ"}, ...]`.

### 1.4 Ceník (z `get_info`)

* `ticket_meal`, `price_ticket_no_meal`, `price_ticket_only_friday`, `price_ticket_no_pii`, `price_one_night` – použít reálné hodnoty z testovací konfigurace.

### 1.5 Meals & Workshops (stavové kombinace)

* **Meal A**: `disable=false`, `warning_show=false`.
* **Meal B**: `disable=true` (vyprodáno).
* **Workshop X**: `disable=false`, `warning_show=true` (blíží se limitu).
* **Workshop Y**: `disable=true`.

### 1.6 Ubytování (z `get_accormodation_info()`)

* Pro **male** i **female** připravit matice:

  * `friday_saturday`: kombinace `disable=false/true`, `warning_show=false/true`, `waring_threshold`.
  * `saturday_sunday`: totéž.

---

## 2) Neshody validací FE vs BE (nutné testovat)

* **Last name:** FE dovolí libovolné ne‑prázdné (po oříznutí mezer), BE vyžaduje `namePattern` (pouze písmena + mezery). Testy musí pokrýt jména s pomlčkou, apostrofem, více mezerami.
* **Address:** FE má regex `addressPattern`, BE kontroluje jen `!= null` (ne `''`). Ověřit prázdný string vs null.
* **Birthday:** FE ukládá `selectedDate` a různé převody na ISO (+1 den v jednom watcheru, přímé `toISOString` v DatePickeru). Testy na off‑by‑one a formát `YYYY-MM-DD`.

---

## 3) Frontend – unit/UI test cases

### 3.1 Email verifikace & OTP

1. **Email již verifikován**
   Vstup: `verif_ok@example.com` → klik na „Verify“.
   Oček.: `api_login.email_verify` vrátí `result=true` → `email_verified()` → `EmailVerified=true`, `showVerify=false`, toast success.
2. **Email není verifikován (OTP odeslán)**
   Vstup: `need_otp@example.com` → klik „Verify“.
   Oček.: `result=false`, `showOtp=true`, `isEmailInValid=true`.
3. **OTP správné (auto-verify po 6 znacích)**
   Vstup: zadat 6 správných číslic → `verify_otp()` → toast success; UI jako v (1).
4. **OTP chybné**
   Vstup: 6 špatných číslic → toast error `message_otp_verification_failed`.
5. **OTP délka ≠ 6**
   Vstup: 5 znaků → `verify_otp()` se nespustí (volá se až při délce 6).
6. **Nový email (DB insert + OTP)**
   `new_user@example.com` → klik „Verify“ → `result=false`, `showOtp=true`.

### 3.2 PII přepínač (no\_pii)

7. **Zapnutí no\_pii**
   Akce: toggl → oček.: `money += price_ticket_no_pii`; validace osobních polí = `true`; vymazání hodnot; gender reset → zakáže ubytování; toast info s novou cenou.
8. **Vypnutí no\_pii**
   Akce: toggl zpět → `money -= price_ticket_no_pii`; validace osobních polí = `false`; `touched.*` zpět na `false`; toast info.

### 3.3 Osobní údaje – validace polí

9. **First name – platný**: Unicode písmena + mezera ("Jan Pavel") → valid=true.
10. **First name – neplatný**: číslice/symboly ("P3tr") → FE invalid + Popover s hláškou.
11. **Last name – prázdné po trimu**: jen mezery → FE invalid.
12. **Last name – platí ve FE, selže v BE**: např. "O'Connor" či "Novák‑Š" (pomlčka) – FE true, BE false.
13. **Birthday – formát**: vybrat datum → `YYYY-MM-DD` uložen; `touched.birthday && !valid.birthday` = false.
14. **Birthday – off‑by‑one**: ověřit, že nastavení přes `selectedDate` (+1 den ve watcheru) dává shodný výsledek s `DatePicker @update` (zda nevzniká posun).
15. **Gender – výběr**: `male`/`female` → valid=true; jiná hodnota → false.
16. **Address – neplatná dle FE regexu**: jen whitespace → invalid + popover.
17. **ZIP – s mezerou**: `123 45` → valid.
18. **ZIP – bez mezery**: `12345` → valid (regex `\s?`).
19. **ZIP – neplatný**: `12 345`/`1234`/`abcde` → invalid.

### 3.4 Programové části

20. **only\_friday=true** (z route) → valid.program\_parts=true i bez výběru.
21. **only\_friday=false** a zaškrtnut pouze pátek → FE invalid (požadováno alespoň jedno z `sat1|2|3`).
22. **Vybrán `sat1`** → FE valid (nezávisle na pátku).

### 3.5 Oběd (meal)

23. **meal flag on** (`static_config.meal=true`) a nic nevybráno → FE invalid.
24. **Vybrán dostupný meal** (A) → FE valid.
25. **Vybrán vyprodaný meal** (B) – UI neumí vybrat (input vykreslen jen když `!disable`), ale BE test pokrývá přechodné stavy (viz 5.3).

### 3.6 Ubytování

26. **Gender nevybrán → přepínač disable** + text s nápovědou.
27. **Zapnuto `want_accommodation`, vybrána 1 noc** → FE valid; `money += price_one_night`, toast.
28. **Zapnuto `want_accommodation`, vybrány 2 noci** → `money += 2*price_one_night`, toast.
29. **Vypnuto `want_accommodation`** → automaticky odškrtnout obě noci; cena snížit; MoneyOnlyAccommodation = 0.
30. **Není vybrána žádná noc, ale `want_accommodation=true`** → FE invalid.
31. **Kombinace s `no_pii` → gender reset**: zapnout `no_pii` během vybraného ubytování → přepnout `want_accommodation=false`, cena se upraví dolů.

### 3.7 Cenotvorba a souhrn

32. **Výchozí cena**:

* `only_friday=true` → `default_price=price_ticket_only_friday`.
* `meal=true` → `default_price=ticket_meal`.
* jinak → `default_price=price_ticket_no_meal`.

33. **Přičítání**: `no_pii` (+), každá noc ubytování (+).
34. **Zobrazení v souhrnu**: hodnoty v tabulce (`default_price`, `price_no_pii`, `MoneyOnlyAccommodation`, `money`) odpovídají realtime výpočtu.

### 3.8 Odeslání (toggle1 → get\_ticket)

35. **Neplatný formulář** → otevře Popover se seznamem položek a zároveň volá `get_ticket()` (FE kód to dělá takto). Ověřit, že se nespustí redirect, ale přijde chybový toast z BE.
36. **Platný formulář** → `get_ticket()` → podle BE odpovědi redirect nebo chybové hlášky.

---

## 4) Backend – API test cases

### 4.1 `get_info`

1. **Základní**: `result=true`, položky `meal`, `workshops`, `accormodation`, `prices` s očekávanými klíči.
2. **Integrita cen**: všechny cenové klíče existují a jsou numerické.
3. **Integrita indexů**: meal/workshops indexované tak, aby FE `Object.values(..)` a následné indexování dle `id` v `get_ticket` fungovalo.

### 4.2 `email_verify`

4. **Email verify = true** → `result=true` (bez odeslání OTP).
5. **Email verify = false** → `result=false`, v DB aktualizován `otp`, byl zavolán `EmailSender::send_otp`.
6. **Email neexistuje** → insert + odeslání OTP.

### 4.3 `verify_otp`

7. **Správné OTP** → `result=true`, DB update: `verify=true`, `otp=''`.
8. **Chybné OTP** → `result=false`, `response=message_otp_verification_failed`.
9. **Email neexistuje v DB** → `result=false` (aktuální kód vrací false bez response; očekávané chování definujte – doporučení: jednotná chyba).

### 4.4 `get_ticket`

**Příprava:** `get_info()` volání uvnitř funkce.

10. **Kontrola ceny – shoda**: FE pošle správnou `price` → `result` nezměněn touto kontrolou.
11. **Kontrola ceny – neshoda**: FE pošle nižší/vyšší `price` → backend přepočítá, nastaví `result=false`, `response` obsahuje `price_mess, ` a `form[pay]` opraví na spočítanou hodnotu.
12. **Email not verified**: DB\_emails.`verify=0` → `result=false`, `response` obsahuje `email_not_verified`.
13. **no\_pii=false – validace jména**:

    * `first_name` neprojde `namePattern` → `ticket_first_name_invalid`.
    * `last_name` neprojde `namePattern` → `ticket_last_name_invalid`.
14. **no\_pii=false – birthday**: formát != `YYYY-MM-DD` → `ticket_birthday_invalid`.
15. **no\_pii=false – address**: `null` → `ticket_address_invalid` (pozor: prázdný string BE neodchytí – viz neshoda).
16. **no\_pii=false – zip**: regex nevyhoví → `ticket_zip_invalid`.
17. **no\_pii=false – gender**: hodnota jiná než `male|female` → `ticke_gender_invalid` (pozn.: tip: překlep v klíči "ticke\_...").
18. **Program parts – only\_friday=true**: backend přepíše části dle pravidel; nemá selhat.
19. **Program parts – only\_friday=false a žádná sobotní část** → `part_mess`.
20. **Meal – meal=true a položka mezitím vyprodána**: backend zjistí `disable=true` → nastaví `form.meal=null`, `meal_not_available`.
21. **Meal – meal=false**: backend vynutí `form.meal=null`.
22. **Workshop – mezitím vyprodán**: `workshop_not_available` a `form.workshop=null`.
23. **Duplicitní registrace**: DB\_event obsahuje kombinaci email+first\_name+last\_name → `ticket_already_exists`.
24. **Úspěch**: žádné chyby → vytvoří `id` jako `cmac_sha256(email+first_name+last_name)`, vloží do DB\_event, vygeneruje `sign` (CMAC), pošle email s odkazem; `response = url_ticket?id=...&sign=...`.
25. **Selhání insertu**: `insert_row` vrátí false → `error_comm_database`.

---

## 5) E2E Scénáře (happy/edge paths)

1. **Plná registrace bez jídla, s 1 nocí ubytování (male)**

   * route: `only_friday=false`, `meal=false`.
   * Email verifikován.
   * Vyplnit osobní data (valid).
   * Gender `male`; `want_accommodation=true`; zvolit `fri_to_sat`.
   * Program: `sat1` zvolen.
   * Odeslat → redirect na URL ticketu, email odeslán.
   * Cena: `price_ticket_no_meal + price_one_night`.

2. **Pouze pátek (only\_friday=true), no\_pii zapnuto (bez osobních dat)**

   * Email verifikován.
   * `no_pii=true` → + `price_ticket_no_pii`.
   * Ubytování nedostupné (gender reset).
   * Odeslat → redirect.
   * Cena: `price_ticket_only_friday + price_ticket_no_pii`.

3. **Balíček s jídlem (meal=true), workshop vyprodán při odeslání**

   * Vybrat dostupný workshop X; během testu backend přepne `disable=true`.
   * Odeslat → BE: `workshop_not_available`, `result=false`; FE zobrazí chybu, uživatel zvolí jiný workshop, znovu odešle → úspěch.

4. **Neověřený email → OTP flow → úspěch**

   * `need_otp@example.com` → Verify → zobrazí OTP vstup.
   * Zadá špatné OTP → error; zadá správné OTP → úspěch; vyplní zbytek → odeslat → redirect.

5. **Cenová neshoda (manipulace FE)**

   * Uměle změnit `money` na nižší částku před odesláním.
   * BE vrátí `price_mess` + nastaví interně `pay` na správnou cenu → `result=false`.
   * Uživatel odešle podruhé beze změn (nebo FE opraví) → úspěch.

6. **Duplicitní registrace**

   * Odeslat stejné `email+first_name+last_name` podruhé → `ticket_already_exists`.

7. **Vyprodáno ubytování (gender=female)**

   * `want_accommodation=true`, `fri_to_sat` má `disable=true` → v UI je tile visual disable (checkbox se nena-renderuje), nelze vybrat; backend nemá co kontrolovat.
   * Pozměnit stav těsně před odesláním (simulace závodu) → BE by měl odmítnout pouze pokud by FE poslal true, ale to FE nepošle, protože checkbox nebyl dostupný.

8. **FE/BE validace příjmení v nesouladu**

   * Last name `O'Connor` → FE projde (není prázdné), BE vrátí `ticket_last_name_invalid` → chyba.

9. **Birthday off‑by‑one**

   * Nastavit datum a ověřit, že uložené `formVal.birthday` odpovídá vybranému dni (bez posunu).
   * E2E potvrdit, že BE přijímá formát i hodnotu.

---

## 6) Hraniční hodnoty & formáty

* **Jména**: diakritika, víceslovná jména, jedno písmeno, extrémně dlouhé (max. délky dle DB schématu – doplnit).
* **Adresa**: obsah čísel, čárek, lomítek; čisté mezery (FE odmítá).
* **PSČ**: s mezerou i bez, začínající nulou.
* **Datum**: 1900‑01‑01, dnešní datum, budoucí datum (pokud by bylo potřeba omezit).
* **Email**: RFC formát vs jednoduchý; FE nevaliduje pattern, validace je implicitní přes BE ověření.

---

## 7) Chybové stavy, toasty a popisky

* Ověřit texty i18n klíčů pro: success verify, resend OTP, price update, meal/workshop unavailable, not verified email, part\_mess, atd.
* Ověřit, že Popover seznam „something is missing“ odkazuje na správné anchor ID a ikony odrážejí `valid.*`.

---

## 8) Bezpečnost & negativní testy

* **Injection**: vložit HTML/script do textových polí → FE jen zobrazí jako text; BE by měl správně escapovat při DB insertu.
* **Neexistující ID meal/workshop**: FE pošle neplatné ID → BE by měl ošetřit indexaci (aktuální kód indexuje bez ověření – riziko `Undefined index`).
* **Manipulace s cenou**: již pokryto (5).
* **Race conditions**: vyprodání položek mezi `get_info` a `get_ticket` – pokryto (workshop/meal checks).
* **Rate limiting**: opakované `email_verify`/`verify_otp` (není implementováno) – doporučení na test pozorování chování.

---

## 9) Návrhy na zlepšení (volitelně)

* Sladit validaci `last_name` FE s BE (`namePattern`).
* FE: `addressPattern` vs BE `null` – sjednotit; BE by měl validovat neprázdný string a povolené znaky.
* Opravit překlep klíče chyby `ticke_gender_invalid`.
* Zvážit jednotnou manipulaci s datem narození (odstranit dvojí nastavování a posun `+1`).
* `toggle1`: volat `get_ticket()` až když `isFormValid` je true; jinak pouze otevřít Popover.
* BE: kontrola existencí indexů `meal`/`workshop` před přístupem.

---

## 10) Akceptační kritéria

* Uživatel s verifikovaným emailem a platnými vstupy vždy obdrží ticket link a email.
* FE nikdy nezobrazí vyprodané položky jako volitelné; BE zachytí závodní stavy.
* Neshody validací jsou vyřešeny (nebo zdokumentovány a pokryty testy).
* Cena je vždy korektně spočítána a případná neshoda je komunikována.

---

## 11) Check-list pro release

* [ ] `get_info` vrací kompletní a konzistentní data.
* [ ] OTP emaily doručitelné (správný subject/body pro jazyky).
* [ ] Ticket email obsahuje validní podepsaný odkaz.
* [ ] E2E scénáře 1–9 prošly.
* [ ] Regrese známých chyb opravena.

# Test Plan & Test Cases – Rozšířená verze

**Projekt:** Webový registrační formulář (Vue 3 + PrimeVue / PHP backend)

---

## 12) Další pokrytí testů

### 12.1 Testování jazykových verzí (i18n)

* **Přepnutí jazyka** před i během vyplňování formuláře – texty tlačítek, popisů, chybových hlášek odpovídají zvolenému jazyku.
* **OTP e-mail** přijde v aktuálním jazyce nastaveném při odeslání požadavku `email_verify`.
* **Ticket e-mail** má správný překlad předmětu, textu a tlačítka.

### 12.2 UI/UX testy

* Responzivní zobrazení formuláře na mobilu, tabletu, desktopu.
* Správná viditelnost/skrývání sekcí (`no_pii`, `only_friday`, `meal`, `EmailVerified`).
* Zajištění, že vypnuté volby (`disable=true`) nejsou klikatelné a nelze je vybrat ani pomocí klávesnice.
* Ověřit správné zobrazování upozornění (`warning_show`) v jídlech, workshopech a ubytování.

### 12.3 Edge cases datových vstupů

* **Email** s velkými písmeny – BE kontrola by měla být case-insensitive.
* **Jméno** s diakritikou a kombinací více jazyků (např. „José Černý“).
* **PSČ** začínající nulou (např. „012 34“).
* **Datum narození**: dnešní den, budoucí datum (ověřit, zda není omezeno), extrémně staré datum.
* **Adresa** obsahující všechny povolené speciální znaky `,./-`.

### 12.4 Výkonnostní testy

* Otestovat odezvu API při velkém počtu souběžných požadavků `email_verify` a `get_ticket`.
* Ověřit, že `get_info` vrací odpověď v přiměřeném čase (< 500 ms v běžném zatížení).

### 12.5 Bezpečnostní testy – rozšíření

* **SQL Injection**: pokus o vložení `"' OR '1'='1"` do textových polí – BE musí správně escapovat.
* **CSRF**: otestovat, zda endpointy nelze spustit bez správného zdroje/hlaviček.
* **Rate limiting**: opakované požadavky OTP – doporučení pro budoucí implementaci.
* **XSS**: vložení `<script>` do `address` či `first_name` – FE/BE nesmí vykreslit kód.

### 12.6 Testování po změnách konfigurace

* Změna ceny v `config_handler` → okamžité promítnutí do FE po reloadu (`get_info`).
* Vyprodání položky (meal/workshop/ubytování) během otevřeného formuláře → BE odmítne při `get_ticket` a FE zobrazí odpovídající hlášku.

### 12.7 Testování chybových hlášek

* Ověřit, že pro každý typ chyby z BE existuje překlad a FE jej zobrazí čitelně.
* Simulovat `error_comm_database` při každém API volání a ověřit zobrazení toastu.

### 12.8 Regresní testy po úpravách

* Po změnách validace (např. sjednocení `last_name`) zopakovat testy 3.3 a 4.4.
* Po úpravě cenotvorby zopakovat testy 3.7 a 4.4 body 10–11.

---

## 13) Dodatečný Check-list před nasazením

* [ ] Všechny jazyky obsahují překlady pro nové i stávající klíče.
* [ ] Překlepy v klíčích (`ticke_gender_invalid`) opraveny.
* [ ] FE a BE validace sjednoceny u jména, adresy, příjmení.
* [ ] Datum narození správně synchronizováno bez posunu dne.
* [ ] Všechny scénáře E2E prošly minimálně ve 2 různých prohlížečích.
* [ ] API je odolné vůči neplatným indexům a chybějícím hodnotám.

---

## 12) Matice konfigurací (route → `static_config` → očekávané UI)

| URL query           | `static_config.only_friday` | `static_config.meal` | Viditelné sekce                                                              | Výchozí cena               |
| ------------------- | --------------------------: | -------------------: | ---------------------------------------------------------------------------- | -------------------------- |
| *(bez parametrů)*   |                       false |                false | Program, Workshop, Ubytování (po gender), **Bez** Lunch                      | `price_ticket_no_meal`     |
| `?only_friday=true` |                        true |                false | Pouze pátek; skrýt Program výběr (auto‑fri), skrýt Lunch; Ubytování dostupné | `price_ticket_only_friday` |
| `?meal=true`        |                       false |                 true | Program výběr, Lunch povinný, Ubytování po gender                            | `ticket_meal`              |

Testy: načtení stránky ve třech variantách a ověřit výchozí cenu a sekce.

---

## 13) Výpočet ceny – oracle & tabulka příkladů

**Vzorec (FE očekávání = BE pravda):**

```
base = only_friday ? price_ticket_only_friday : (meal ? ticket_meal : price_ticket_no_meal)
add_no_pii = no_pii ? price_ticket_no_pii : 0
add_accom = (fri_to_sat?1:0 + sat_to_sun?1:0) * price_one_night
money = base + add_no_pii + add_accom
```

**Příklady:**

1. `only_friday, no_pii, 0 nocí` → `price_ticket_only_friday + price_ticket_no_pii`
2. `meal, 2 noci` → `ticket_meal + 2*price_one_night`
3. `no_meal, 0 nocí` → `price_ticket_no_meal`
   **Negativní:** FE pošle menší `money` → BE vrátí `price_mess` a přepočtenou cenu v `form.pay`.

---

## 14) Lokalizace (i18n) – test cases

* **Jazyky:** minimálně `cs`, `en` (dle `i18n.global.t('lang_code')`).
* **OTP email texty:** správné titulky/obsah pro vybraný jazyk.
* **Toasty & popisky:** klíče existují: `verify_email_success`, `price_update_title`/`_text`, `accommodation_warning_message`, `lunch_warning_message`, `unavailable`, `available`, `ticket_something_is_missing`, aj.
* **Fallbacky:** při chybějícím klíči se nezobrazí syrový klíč; přidej test na `ticke_gender_invalid` (překlep) – očekávané chování.

---

## 15) Přístupnost (a11y)

* Navigace klávesnicí (Tab pořadí, fokus prstence viditelné).
* `aria-label`/`aria-describedby` u tlačítek Verify/Resend OTP, u checkbox tiles.
* Kontrast textů v popoverech (červený text na tmavém pozadí).
* `InputOtp` – čtení čtečkou, ozvučení chyb.
* Chybové stavy musí být hlášeny i netextově (role="alert" u Toast/Message).
* Datumovka přístupná z klávesnice.

---

## 16) Cross‑browser / responzivita

* Prohlížeče: Chromium (Edge/Chrome), Firefox, Safari (macOS/iOS).
* Šířky: 320px, 768px, 1024px, 1440px.
* Obrázky v kartách meal/workshop nevytečou z kontejneru; grid se neláme.

---

## 17) Výkon a stabilita

* `get_info` do 500 ms (lokál), 1.5 s (staging).
* Debounce u tlačítek Verify/Resend (nebo server guard) – otestovat dvojklik.
* `get_ticket` idempotence na úrovni serveru (duplicitní klik).
* Velikost bundlu stránku nenačítá > 2 s při 3G Fast (lighthouse smoke test).

---

## 18) Logování, monitoring, observabilita

* BE loguje pokusy o OTP, chyby DB, cenové nesoulady, odmítnuté workshopy/meals.
* Korelace tiketů pomocí `form.id`.
* Maskování PII v logách, zejména při `no_pii=true`.

---

## 19) Databázové a datové testy

* **Konzistence DB\_emails:** `verify` přepínán pouze přes OTP/verify flow; `otp` po úspěchu vyprázdněn.
* **DB\_event unikátnost:** unikátní index na `(email, first_name, last_name)` – pokud není, test odhalí duplicitní insert.
* **Referenční integrita:** hodnoty `meal`/`workshop` korespondují s ceníkem/konfigurací v čase zápisu.

---

## 20) Zabezpečení (rozšíření)

* **Rate‑limiting:** simulace bruteforce OTP (např. 20 pokusů/min) – očekávaný blok/ochrana (zatím návrh).
* **OTP expirace:** test s prošlou platností (pokud bude doplněna).
* **CSRF/Replay:** `get_ticket` POST jen s očekávaným CSRF (pokud používáte).
* **CMAC/ID:** ověřit, že `sign` odpovídá `cmac_sha256(id)` a URL není podvržitelná.

---

## 21) Selektory a test IDs (doporučení pro E2E)

Doplň do DOM atributy `data-testid` pro stabilní selektory, např.:

* `data-testid="email-input"`, `verify-button`, `otp-input`, `no-pii-toggle`, `first-name`, `last-name`, `birthday`, `gender-male`, `gender-female`, `address`, `state`, `zip`, `accom-toggle`, `fri-sat`, `sat-sun`, `program-sat1/2/3`, `meal-option-<id>`, `workshop-option-<id>`, `get-ticket`, `missing-popover`.

---

## 22) Cypress – návrh test speců

```
// cypress/e2e/registration.cy.ts
describe('Registration form', () => {
  it('verifies email instantly when already verified', () => { /* ... */ });
  it('handles OTP flow incl. invalid/valid codes', () => { /* ... */ });
  it('toggles no_pii and updates price & validations', () => { /* ... */ });
  it('validates fields and shows popover anchors', () => { /* ... */ });
  it('enforces program parts depending on route flags', () => { /* ... */ });
  it('computes price correctly for combos', () => { /* ... */ });
  it('blocks submission on price mismatch returned by BE', () => { /* ... */ });
  it('submits successfully and redirects to ticket URL', () => { /* ... */ });
});
```

Mockuj `api_post` přes `cy.intercept` na `endpoint_login`/`endpoint_ticket` s různými fixturnami odpovědí.

---

## 23) Postman/Bruno – API kolekce (návrh)

* **Folder `login`**

  * `email_verify` (200): body `{method:"email_verify", parameters:{email, lang}}` → `result=true|false`.
  * `verify_otp` (200): `{method:"verify_otp", parameters:{email, otp}}` → `result=true|false`.
* **Folder `ticket`**

  * `get_info` (200): `{method:"get_info"}` → zkontrolovat schéma.
  * `get_ticket` (200): `{method:"get_ticket", parameters:{form, static_cfg, price, lang}}` → různé negativní kombinace (`email_not_verified`, `part_mess`, `price_mess`, ...).
    Zahrň schematické testy (JSON Schema) pro klíče a typy.

---

## 24) Regressní sada (po změnách validací/cen)

* Po změně regexů jmen – projeď scénáře 9–12 a e2e success.
* Po změně ceníku – scénáře 32–34 + 10–11 (BE kontrola ceny).
* Po změně i18n – sekce 14.

---

## 25) Známé slabiny / TODO

* FE vs BE regexy (last\_name, address).
* Potřeba server‑side validace `address` na neprázdný ne‑whitespace.
* Chybí rate‑limit/expirace OTP.
* `toggle1` volá `get_ticket()` i při nevalidním formuláři – zvážit změnu UX.

---

## 26) Úklid testovacích dat

* Skripty pro smazání e2e účastníků z `DB_event` a reset `DB_emails.verify/otp`.
* Označ testovací emaily prefixem `test+<case>@example.com` pro snazší cleanup.
