# Moduł PayU dla OpenCart wersja 3.x
``Moduł jest wydawany na licencji GPL.``

**Jeżeli masz jakiekolwiek pytania lub chcesz zgłosić błąd zapraszamy do kontaktu z naszym [wsparciem technicznym][ext7].**

* Jeżeli używasz OpenCart w wersji 2.3.x proszę skorzystać z [pluginu w wersji 3.2.x][ext1]
* Jeżeli używasz OpenCart w wersji 2.0.x, 2.1.x lub 2.2.x proszę skorzystać z [pluginu w wersji 3.1.x][ext2]

## TODOs
- Canceled notification should send an email to the customer with a configurable message

## Difference between this and the [original repo](https://github.com/PayU-EMEA/plugin_opencart_3)
- Shows error on fail
- Lets you use the sandbox environment

The repo is primarily for internal use but I made it public in case it helps anyone. There's no guarantee of support or long-term maintenance.

To differentiate between this repo and the original one, we use our own versioning, starting from 1.0.0.

## Testing cards

| Card number      | Expiration | CVV | Behavior                          |
|------------------|------------|-----|-----------------------------------|
| 4444333322221111 | 12/29      | 123 | Pass                              |
| 5100052384536818 | 02/32      | 123 | Lets you fail if you deny the 3DS |

For more, see [the official docs](https://developers.payu.com/en/overview.html#sandbox_cards).

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
