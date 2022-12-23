# Moduł PayU dla OpenCart wersja 3.x
``Moduł jest wydawany na licencji GPL.``

## Difference between this and the [original repo](https://github.com/PayU-EMEA/plugin_opencart_3)
- Shows error on fail (instead of a success message — on the `checkout/success` route)
- [Lets you use the sandbox environment](#local-development)
- [Lets you send customers email notifications](#customer-notifications)

The repo is primarily for internal use but I made it public in case it helps anyone. There's no guarantee of support or long-term maintenance.

To differentiate between this repo and the original one, we use our own versioning, starting from 1.0.0.

You can view all of the changes here: [`bfcfc5b...HEAD`](https://github.com/stancl/payu_opencart_3/compare/bfcfc5b2d07ecf7030d680e9e6657c14765086e1...HEAD)

## Installation

[Download this repo as a zip](https://github.com/stancl/payu_opencart_3/archive/refs/heads/master.zip), extract the folder, and then compress the *contents of that folder* into a new zip that ends in `.ocmod.zip`. Meaning: `install.xml` should be at the root of the zip file.

Alternatively, you can clone the repo and run `./build.sh` (if you're on Mac/Linux) to create `payu.ocmod.zip`.

## Customer notifications

One of the features this fork adds is notifying customers when the PayU status of their order changes.

To enable this, simple fill out the *PayU Notifications email* fields in the extension config, for each status that you want to notify the customer about.

A few notes:
- Per [the docs](https://developers.payu.com/en/restapi.html#status_update): *"To enable `WAITING_FOR_CONFIRMATION` status, payment methods on your POS need to have auto-receive turned off. Otherwise, all successful payments for an order will automatically trigger `COMPLETED` status."*. This means that if you have auto-receive enabled (you most likely do), you won't receive the `WAITING_FOR_CONFIRMATION` status at all.
- On successful payments, the flow is like this:
    - `Status of the new transaction` set
    - After the customer makes a payment: `PayU Notifications Status: Pending` is set
    - Immediately afterwards: `PayU Notifications Status: Completed` is set
    - **Therefore**: you likely don't want to be sending notifications for the *Pending* status
- The same happens with unsuccessful payments. The only difference is that the last notification is for `Canceled` instead of `Completed`, but the rest of the flow is identical (including `Pending` being sent first).

If the *PayU Notifications email* field is empty (for any status), the extension will not notify the customer about the status being set, and it will use `PayU Notification` as the comment, to make it clear that the status comes from PayU.

## Testing cards

| Card number      | Expiration | CVV | Behavior                          |
|------------------|------------|-----|-----------------------------------|
| 4444333322221111 | 12/29      | 123 | Pass                              |
| 5100052384536818 | 02/32      | 123 | Lets you fail if you deny the 3DS |

For more, see [the official docs](https://developers.payu.com/en/overview.html#sandbox_cards).

## Sandbox credentials

The sandbox credentials mentioned in the docs are:

| Key                 | Value                            |
|---------------------|----------------------------------|
| POS ID              | 145227                           |
| Second key (MD5)    | 13a980d4f851f3d9a1cfc792fb1f5e50 |
| OAuth client_id     | 145227                           |
| OAuth client_secret | 12f071174cb7eb79d4aac5bc2f07563f |

However, these don't seem to work on my end. So I use a custom POS created in the sandbox environment.

Since I use ngrok (see the section below), I have to create a new POS every time I have a new ngrok subdomain.

### Local development

To play with the sandbox environment locally, I do this:
- host the site using simple `php -S` (`/opt/homebrew/Cellar/php@7.3/7.3.33_3/bin/php -S localhost:8888` since I'm using a specific binary to match OC's PHP version)
- share the site using `ngrok http 8888`
- modify `config.php` to use the `HTTP_HOST` for `HTTP_SERVER` & `HTTPS_SERVER` (though you have to revert this when using the site normally w/o ngrok):

```diff
- define('HTTP_SERVER', 'http://opencart.test/');
+ define('HTTP_SERVER', 'http://' . $_SERVER['HTTP_HOST'] . '/');
- define('HTTPS_SERVER', 'http://opencart.test/');
+ define('HTTPS_SERVER', 'https://' . $_SERVER['HTTP_HOST'] . '/');
```

(`opencart.test` being the local domain I use to visit the site without ngrok)

To test email, I use [Mailtrap](https://mailtrap.io/).

## Development notes

- The repo uses `canceled` and `cancelled` inconsistently. I kept the inconsistency for backwards compatibility with the old repo (and old config data).
    - `CANCELED` is used in the context of PayU responses
    - `cancelled` is used in the context of language strings, config keys, and everything that relates to the extension settings page

**Jeżeli masz jakiekolwiek pytania lub chcesz zgłosić błąd zapraszamy do kontaktu z naszym [wsparciem technicznym][ext7].**

* Jeżeli używasz OpenCart w wersji 2.3.x proszę skorzystać z [pluginu w wersji 3.2.x][ext1]
* Jeżeli używasz OpenCart w wersji 2.0.x, 2.1.x lub 2.2.x proszę skorzystać z [pluginu w wersji 3.1.x][ext2]

## Spis treści

* [Cechy i kompatybilność](#cechy-i-kompatybilność)
* [Wymagania](#wymagania)
* [Instalacja](#instalacja)
* [Aktualizacja](#aktualizacja)
* [Konfiguracja](#konfiguracja)

## Cechy i kompatybilność
Moduł płatności PayU dodaje do OpenCart opcję płatności PayU i umożliwia:

* Utworzenie płatności (wraz z rabatami)
* Automatyczne odbieranie powiadomień i zmianę statusów zamówienia

## Wymagania

**Ważne:** Moduł ta działa tylko z punktem płatności typu `REST API` (Checkout), jeżeli nie posiadasz jeszcze konta w systemie PayU - [**Zarejestruj się**][ext6]

Do prawidłowego funkcjonowania modułu wymagane są następujące rozszerzenia PHP: [cURL][ext3] i [hash][ext4].

## Instalacja

1. Pobierz moduł z [repozytorium GitHub][ext5] jako plik zip.
1. Rozpakuj pobrany plik.
1. Połącz się z serwerem ftp i skopiuj zawartość katalogu `upload` z rozpakowanego pliku do katalogu głównego swojego sklepu OpenCart.
1. Przejdź do strony administracyjnej swojego sklepu OpenCart [http://adres-sklepu/admin].
1. Przejdź  `Extensions` » `Extensions`.
1. Ustaw filtr na `Payments`.
1. Znajdź na liście `PayU` i kliknij w ikonę `Install`.

## Konfiguracja

1. Przejdź do strony administracyjnej swojego sklepu OpenCart [http://adres-sklepu/admin].
1. Przejdź  `Extensions` » `Extensions`.
1. Ustaw filtr na `Payments`.
1. Znajdź na liście `PayU` i kliknij w ikonę `Edit`.

#### Parametry konfiguracyjne


| Parameter | Opis |
|---------|-----------|
| Status |Określa czy metoda płatności PayU będzie dostępna w sklepie na liście płatności.|
| Kolejność |Określa na której pozycji ma być wyświetlana metoda płatności PayU dostępna w sklepie na liście płatności.|
| Suma zamówienia |Minimalna wartość zamówienia, od której metoda płatności PayU dostępna w sklepie na liście płatności.|
| Strefa Geo |Strefa Geo, dla której metoda płatności PayU dostępna w sklepie na liście płatności.|
| Id punktu płatności | Identyfikator POS-a z systemu PayU |
| Drugi klucz (MD5) | Drugi klucz MD5 z systemu PayU |
| Protokół OAuth - client_id | client_id dla protokołu OAuth z systemu PayU |
| Protokół OAuth - client_secret | client_secret for OAuth z systemu PayU |

#### Patametry statusów
Określa relacje pomiędzy statusami zamówienia w PayU a statusami zamówienia w OpenCart.

<!--LINKS-->

<!--external links:-->
[ext0]: README.EN.md
[ext1]: https://github.com/PayU/plugin_opencart_2
[ext2]: https://github.com/PayU/plugin_opencart_2/tree/opencart_2_2
[ext3]: http://php.net/manual/en/book.curl.php
[ext4]: http://php.net/manual/en/book.hash.php
[ext5]: https://github.com/PayU/plugin_opencart_3
[ext6]: https://www.payu.pl/oferta-handlowa
[ext7]: https://www.payu.pl/pomoc

<!--images:-->
