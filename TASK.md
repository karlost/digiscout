# Návrh monitorovací aplikace v Laravel s Backpack administrací

## Přehled aplikace

Aplikace bude sloužit k monitorování různých webových stránek pomocí několika monitorovacích nástrojů. Hlavní funkcionalita bude spočívat v pravidelné kontrole dostupnosti a výkonu webů podle nastavených parametrů. Celá aplikace bude spravována přes administrační rozhraní vytvořené pomocí Backpack for Laravel.

## Databázový model

### Websites
- **id**: int, primární klíč
- **url**: string, URL monitorovaného webu
- **name**: string, název webu pro snadnou identifikaci
- **description**: text, volitelný popis webu
- **status**: boolean, aktivní/neaktivní
- **created_at**: timestamp
- **updated_at**: timestamp

### MonitoringTools
- **id**: int, primární klíč
- **name**: string, název nástroje
- **code**: string, jedinečný kód nástroje pro použití v systému
- **description**: text, popis funkcionality nástroje
- **default_interval**: int, výchozí interval kontroly
- **interval_unit**: enum, jednotka intervalu (sekunda/minuta/hodina)
- **created_at**: timestamp
- **updated_at**: timestamp

### WebsiteMonitoringSettings
- **id**: int, primární klíč
- **website_id**: int, cizí klíč
- **monitoring_tool_id**: int, cizí klíč
- **interval**: int, interval kontroly pro konkrétní web a nástroj
- **enabled**: boolean, povoleno/zakázáno
- **threshold**: float, práh pro varování
- **notify**: boolean, zasílat notifikace
- **created_at**: timestamp
- **updated_at**: timestamp

### MonitoringResults
- **id**: int, primární klíč
- **website_id**: int, cizí klíč
- **monitoring_tool_id**: int, cizí klíč
- **status**: enum, úspěch/selhání
- **value**: float, naměřená hodnota
- **check_time**: timestamp, čas kontroly
- **additional_data**: json, další data specifická pro nástroj
- **created_at**: timestamp
- **updated_at**: timestamp

## Monitorovací nástroje

Aplikace bude obsahovat následujících 5 základních monitorovacích nástrojů:

### 1. Ping
- Kontrola dostupnosti serveru pomocí ICMP/HTTP ping
- Měření doby odezvy serveru
- Upozornění při nedostupnosti nebo vysokém čase odezvy

### 2. HTTP Status
- Kontrola HTTP stavového kódu
- Ověřování, zda web vrací očekávaný HTTP kód (typicky 200)
- Detekce chybových kódů (4xx, 5xx)
- Sledování přesměrování a jejich cílů

### 3. DNS Check
- Ověřování DNS záznamů webových stránek
- Kontrola A, AAAA, MX, TXT, CNAME a dalších typů záznamů
- Detekce změn v DNS záznamech
- Kontrola expirace domény
- Upozornění na potenciální problémy s konfigurací DNS

### 4. Load Time
- Měření celkové doby načítání stránky
- Analýza rychlosti načítání jednotlivých komponent
- Upozornění při překročení stanoveného limitu
- Sledování trendů ve výkonu stránky

### 5. SSL Certificate
- Kontrola platnosti SSL certifikátu
- Upozornění na blížící se expiraci certifikátu
- Ověření správné konfigurace SSL
- Detekce problémů s certifikačním řetězcem

## Backpack administrace

### CRUD sekce

#### Websites CRUD
- Seznam všech monitorovaných webů
- Přidávání, úprava a mazání webů
- Filtrování a vyhledávání podle URL, názvu nebo stavu
- Rychlé zapnutí/vypnutí monitoringu pro celý web

#### Monitoring Tools CRUD
- Správa dostupných monitorovacích nástrojů
- Konfigurace výchozích hodnot pro každý nástroj
- Možnost dočasně deaktivovat určité typy monitoringu globálně

#### Website Monitoring Settings CRUD
- Propojení webů s monitorovacími nástroji
- Individuální nastavení intervalu kontroly pro každý web a nástroj
- Konfigurace prahových hodnot pro upozornění
- Nastavení notifikací

#### Monitoring Results
- Přehled všech výsledků monitoringu
- Filtrování podle webu, nástroje, času a stavu
- Export dat do CSV/Excel
- Vizualizace historických dat

### Dashboard
- Přehledová stránka se stavem všech monitorovaných webů
- Grafy úspěšnosti monitoringu v čase
- Rychlý přístup k problémovým webům
- Statistiky a agregovaná data

## Systém zpracování

### Scheduler a Queue
- Využití Laravel Scheduler pro spouštění kontrol v definovaných intervalech
- Implementace fronty úloh (Queue) pro asynchronní zpracování monitorovacích úkolů
- Optimalizace pro velké množství monitorovaných webů
- Distribuce zátěže při špičkách

### Notifikace
- Upozornění při selhání monitoringu nebo překročení prahových hodnot
- Možnosti notifikačních kanálů:
  - Email
  - Webhook (integrace s Discord)
  - Interní notifikace v administraci
- Konfigurace frekvence notifikací (okamžitě, denní souhrn)
- Možnost nastavit různé příjemce pro různé typy upozornění

## Rozšiřitelnost
- Architektura umožňující snadné přidávání nových typů monitorovacích nástrojů
- REST API pro integraci s externími systémy
- Možnost rozšíření o podrobnější analytické nástroje
- Příprava pro budoucí implementaci uživatelských rolí a oprávnění

## Technické požadavky
- Laravel 11 framework
- Backpack for Laravel pro administraci
- MySQL databáze
- Queue worker (Redis/Database)
- Cron pro scheduler
- PHP 8.2+

## Dokumentace a odkazy

Pro implementaci projektu jsou k dispozici následující zdroje dokumentace:

### Základní technologie
- Laravel 11: https://laravel.com/docs/11.x
- Backpack for Laravel: https://backpackforlaravel.com/docs
- PHP 8.2: https://www.php.net/releases/8.2/en.php
- MySQL: https://dev.mysql.com/doc/

### Monitorovací nástroje
- Ping implementace: https://github.com/geerlingguy/Ping
- DNS Check: https://www.php.net/manual/en/function.dns-get-record.php
- SSL Certificate Check: https://www.php.net/manual/en/book.openssl.php

### Integrace a notifikace
- Discord Webhook API: https://discord.com/developers/docs/resources/webhook
- Laravel Notifications: https://laravel.com/docs/11.x/notifications

### Queue a Scheduler
- Laravel Queue: https://laravel.com/docs/11.x/queues
- Laravel Scheduler: https://laravel.com/docs/11.x/scheduling

## Dokumentace a odkazy

Pro implementaci projektu jsou k dispozici následující zdroje dokumentace:

### Základní technologie
- Laravel 11: https://laravel.com/docs/11.x
- Backpack for Laravel: https://backpackforlaravel.com/docs
- PHP 8.2: https://www.php.net/releases/8.2/en.php
- MySQL: https://dev.mysql.com/doc/

### Monitorovací nástroje
- Ping implementace: https://github.com/geerlingguy/Ping
- DNS Check: https://www.php.net/manual/en/function.dns-get-record.php
- SSL Certificate Check: https://www.php.net/manual/en/book.openssl.php

### Integrace a notifikace
- Discord Webhook API: https://discord.com/developers/docs/resources/webhook
- Laravel Notifications: https://laravel.com/docs/11.x/notifications

### Queue a Scheduler
- Laravel Queue: https://laravel.com/docs/11.x/queues
- Laravel Scheduler: https://laravel.com/docs/11.x/scheduling