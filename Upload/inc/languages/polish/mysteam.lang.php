<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2015 Bartłomiej Stawiarz (aka Arhenox)
 *
 * POLISH LANGUAGE FILE
 * Valid for version 1.3
 */

// Title and description for the plugin
$l['mysteam_title'] = "MySteam Powered";
$l['mysteam_desc'] = "Używa Steam Web API by pobierać aktualne statusy użytkowników (którzy powiązali swoje SteamID). Umożliwia także zarządzanie powiązanym SteamID danego użytkownika z Panelu Moderatora.";

// Plugins page messages
$l['mysteam_settings'] = "Ustawienia";
$l['mysteam_profile_editor'] = "Edytor profilu";
$l['mysteam_asb_success'] = "Wykryto Advanced Sidebox. Moduł ASB został pomyślnie zintegrowany.";
$l['mysteam_apikey_needed'] = "Nie został podany klucz Steam Web API. Proszę przejść do Ustawień i go tam wprowadzić.";
$l['mysteam_steamids_needed'] = "Żadnen użytkownik nie ma powiązanego SteamID. Zachęć użytkowników do powiązania swojego konta w Panelu Użytkownika lub samemu połącz konta ze SteamID używając Panelu Moderatora.";
$l['mysteam_asb_upgrade'] = "Twoja wersja Advanced Sidebox nie spełnia wymagań. Zaktualizuj go do wersji 2.1 lub późniejszej.";
 
// Title and description for the ASB module
$l['asb_mysteam_title'] = "Status Steam";
$l['asb_mysteam_desc'] = "WYMAGA pluginu MyBB MySteam Powered! Używa Steam Web API, by wyświetlać aktualne statusy użytkowników.";
$l['mysteam_plugin_needed'] = "Plugin MySteam Powered nie jest aktywowany. Aktywuj go zanim przejdziesz dalej.";

// Settings groups and template group
$l['mysteam_main_group_desc'] = "Skonfiguruj ogólne ustawienia pluginu MySteam Powered i jego modułu Advanced Sidebox (jeżeli w użyciu).";
$l['mysteam_list_group_title'] = "Lista statusów MySteam Powered (nie-Advanced Sidebox)";
$l['mysteam_list_group_desc'] = "Skonfiguruj wbudowaną (nie-Advanced Sidebox) listę statusów Steam.";
$l['mysteam_template_group'] = "MySteam Powered";

// Main settings
$l['mysteam_list_enable_title'] = "Włączyć wbudowaną (nie-Advanced Sidebox) listę statusów?";
$l['mysteam_list_enable_desc'] = "Jeżeli tak, lista statusów jak ta w module Advanced Sidebox będzie wyświetlana na głównej stronie i/lub stronach portalu. Może być użyte z modułem ASB, ale powtórzy funkcjonalność w przypadku użycia na tej samej stronie.";
$l['mysteam_list_settings'] = "Ustawienia listy";
$l['mysteam_apikey_title'] = "Klucz Steam Web API";
$l['mysteam_apikey_desc'] = "Wprowadź klucz Steam Web API dla Twojej strony (zdobądź taki <a href=\"http://steamcommunity.com/dev/apikey\">tutaj</a>).";
$l['mysteam_limitbygroup_title'] = "Ogranicz wyświetlane grupy użytkowników?";
$l['mysteam_limitbygroup_desc'] = "Wprowadź gid każdej grupy, która ma się wyświetlać, oddzielaj przecinkiem. Gid możesz znaleźć w adresie zarządzania daną grupą w Panelu Administratora (np. /index.php?module=user-groups&action=edit&gid=<strong>123</strong>). Zmiany będą miały efekt po kolejnym przeładowaniu cache.";
$l['mysteam_cache_title'] = "Okres życia cache";
$l['mysteam_cache_desc'] = "Ustal jak długo (w minutach) cache powinien być używany przed jego odświeżeniem. Zmniejszenie tej wartości powoduje wyświetlanie bardziej aktualnych danych kosztem zwiększonego obciążenia serwera. 0 wyłącza cache.";
$l['mysteam_displayname_title'] = "Wyświetlana nazwa";
$l['mysteam_displayname_desc'] = "Wybierz, którą nazwę wyświetlać użytkownikom. <i>Obie</i> wyświetla nazwę na forum obok nazwy Steam, lecz tylko, gdy różnią się one od siebie.";
$l['mysteam_displayname_steam'] = "Wyświetlaj nazwę na Steam";
$l['mysteam_displayname_forum'] = "Wyświetlaj nazwę na forum";
$l['mysteam_displayname_both'] = "Wyświetlaj i nazwę Steam, i nazwę na forum";
$l['mysteam_profile_title'] = "Wyświetlać na profilu?";
$l['mysteam_profile_desc'] = "Jeśli tak, aktualny status Steam użytkownika i pole kontaktowe Steam pojawią się na stronie profilu.";
$l['mysteam_postbit_title'] = "Wyświetlać w informacjach o autorze posta?";
$l['mysteam_postbit_desc'] = "Jeżeli tak, aktualny status Steam autora posta będzie wyświetlany w informacjach o nim.";
$l['mysteam_postbit_img'] = "Tak. Wyświetlaj status jako obrazek.";
$l['mysteam_postbit_text'] = "Tak. Wyświetlaj status jako tekst.";
$l['mysteam_postbit_no'] = "Nie";
$l['mysteam_hover_title'] = "Wyświetlać status po najechaniu?";
$l['mysteam_hover_desc'] = "Jeśli tak, tekstowy status autora posta będzie wyświetlany przy najechaniu myszką na obrazek. Jeśli nie, status będzie wyświetlany zawsze. Dotyczy tylko, jeżeli w opcji wyżej wybrano obrazek.";
$l['mysteam_prune_title'] = "Usuwaj nieaktywnych użytkowików z listy";
$l['mysteam_prune_desc'] = "Ustal, po ilu dniach od ostatniej wizyty użytkownik nie powinien dłużej pojawiać się na liście. 0 wyłącza usuwanie. Zmiany będą miały efekt po kolejnym odświeżeniu cache.";
$l['mysteam_usercp_title'] = "Włączyć formularz w Panelu Użytkownika?";
$l['mysteam_usercp_desc'] = "Jeżeli tak, użytkownicy będą mogli używać formularza w Panelu Użytkownika, by dodać informacje o swoim koncie (jeżeli są w dozwolonej grupie).";
$l['mysteam_modcp_title'] = "Włączyć formularz w Panelu Moderatora?";
$l['mysteam_modcp_desc'] = "Jeżeli tak, moderatorzy będą mogli używać formularza w Panelu Moderatora, by ustawić SteamID innym użytkownikom.";

// Settings for both ASB and non-ASB status lists
$l['mysteam_list_width_title'] = "Szerokość każdego wpisu statusu";
$l['mysteam_list_width_desc'] = "Ustaw szerokość (w pikselach) każdego wpisu na liście statusów. To ustawienie kontroluje także ile jest wierszy i kolumn (mniejsza szerokość skutkuje większą ilością kolumn).";
$l['mysteam_list_number_title'] = "Maksymalna liczba wyświetlanych graczy";
$l['mysteam_list_number_desc'] = "Ustaw maksymalną liczbę wyświetlanych graczy na liście. 0 wyłącza tę opcję, więc wszyscy użytkownicy online pojawią się na liście. Zmiany bedą miały miejsce po kolejnym odświeżeniu cache.";

// Settings for ASB module only
$l['mysteam_settings_where_title'] = "Gdzie są wszystkie ustawienia?";
$l['mysteam_settings_where_desc'] = "Większość ustawień tego panelu bocznego znajduje się w głównym menu ustawień MyBB. Nie zapomnij tam się udać i skonfigurować wszystko według potrzeb (panel inaczej nie zadziała!). <strong>PS. Dowolne ostrzeżenia poniżej są ważne tylko w momencie, w których panel został dodany!</strong>";
$l['mysteam_doesnt_do_anything'] = 'To nic nie robi, szczerze!';
$l['mysteam_list_cols_title'] = "Liczba kolumn";
$l['mysteam_list_cols_desc'] = "Jeżeli chcesz, by panel boczny miał wiele kolumn, wpisz tutaj ich liczbę.";

// Settings for non-ASB status list only
$l['mysteam_index_title'] = "Wyświetlać listę statusów na stronie głównej?";
$l['mysteam_index_desc'] = "Jeżeli włączone, lista statusów Steam będzie wyświetlana na głównej stronie.";
$l['mysteam_portal_title'] = "Wyświetlać listę statusów na portalu?";
$l['mysteam_portal_desc'] = "Jeżeli włączone, lista statusów Steam będzie wyświetlana na stronie portalu.";

// Steam status list
$l['mysteam_in_game'] = "W grze";
$l['mysteam_offline'] = "Offline";
$l['mysteam_online'] = "Online";
$l['mysteam_busy'] = "Zajęty";
$l['mysteam_away'] = "Zaraz wracam";
$l['mysteam_snooze'] = "Drzemka";
$l['mysteam_looking_to_trade'] = "Chce się wymienić";
$l['mysteam_looking_to_play'] = "Chce pograć";
$l['mysteam_none_found'] = "Nie udało się połączyć z siecią Steam. Przyczyną może być problem z serwerami Steam, problem z konfiguracją forum lub brak użytkowników, którzy powiązali swoje SteamID. Nowa próba połączenia zostanie nawiązana co 3 minuty (lub przy każdym wejściu na stronę, jeżeli cache jest wyłączony).";
$l['mysteam_complete_list'] = "Pełna lista";

// Member profile page
$l['mysteam_status'] = "Steam:";
$l['mysteam_name'] = "Nazwa Steam: ";

// Steam ID form
$l['mysteam_integration'] = "Powiązanie z kontem Steam";
$l['mysteam_url'] = "Link do profilu Steam:";
$l['mysteam_current'] = "Aktualne SteamID:";
$l['mysteam_integrate'] = "Powiąż konto Steam";
$l['mysteam_search'] = "Wyszukaj profilu Steam używając nazwy z forum";
$l['mysteam_search_manual'] = "Ręczne wyszukiwanie";
$l['mysteam_decouple'] = "Rozłącz z kontem Steam";
$l['mysteam_decouple_body'] = "Odłączenie konta Steam:";
$l['mysteam_powered_by'] = "Strona napędzana dzięki";

// Steam ID form (User CP)
$l['mysteam_usercp_intro'] = "Te forum posiada możliwość wyświetlania Twojego obecnego statusu Steam (online/offline, w grze itp.) oraz informacji kontaktowych Steam na Twoim profilu.";
$l['mysteam_usercp_instruct'] = "By aktywować tę funkcję, kliknij na zielonym przycisku poniżej. Zostaniesz przekierowany do oficjalnej strony Steam, gdzie możesz zalogować się swoimi danymi.";
$l['mysteam_usercp_note'] = "PS. Żeby Twój status był wyświetlany, Twoje konto nie może być ustawione jako prywatne!";
$l['mysteam_usercp_decouple'] = "Jeżeli chcesz rozłączyć konto Steam z forum, wciśnij odpowiedni przycisk poniżej.";

// Steam ID form (Moderator CP)
$l['mysteam_modcp_intro'] = "Te forum posiada możliwość wyświetlania aktualnych statusów Steam użytkowników (online/offline, w grze itp.) oraz informacji kontaktowych Steam na ich profilu.";
$l['mysteam_modcp_instruct'] = "By aktywować tę funkcję dla danego użytkownika, wprowadź adres URL do profilu Steam użytkownika, jego unikatową nazwę niestandardowego adresu profilu (steamcommunity.com/id/[nazwa]) lub jego 64-bitowe Steam ID w polu poniżej, następnie zatwierdź. Możesz użyć poniższych linków by wyszukać profil Steam użytkownika.";
$l['mysteam_modcp_note'] = "PS. Żeby status był wyświetlany, konto użytkownika nie może być ustawione jako prywatne!";
$l['mysteam_modcp_back'] = "Powrót do Panelu Moderatora";
$l['mysteam_modcp_decouple'] = "Jeżeli chcesz rozłączyć powiązane konto Steam tego użytkownika z forum, naciśnij ten przycisk:";

// Steam ID form (submit)
$l['mysteam_submit_invalid'] = "Adres URL lub ID, które podałeś, nie zwróciły prawidłowej odpowiedzi. Albo są niepoprawne, albo serwery Społeczności Steam są aktualnie niedostepne.";
$l['mysteam_submit_same'] = "SteamID tego profilu Steam jest identyczne jak Steam ID następującego użytkownika: ";
$l['mysteam_login_error'] = "Próba wykorzystania błędnego ticketu (prawdopodobnie odświeżyłeś stronę).";
$l['mysteam_steamid'] = "Steam ID: ";
$l['mysteam_vanityurl'] = "Nazwa niestandardowego adresu profilu: ";

// Steam ID form (User CP submit)
$l['mysteam_submit_success'] = "Sukces! Twoje Steam ID zostało połączone. Następująca informacja jest powiązana z kontem, na które się zalogowałeś:";
$l['mysteam_decouple_success'] = "Twoje Steam ID zostało pomyślnie odłączone od konta.";
$l['mysteam_canceled_login'] = "Zamknąłeś stronę logowania. Twoje konta nie zostały połączone.";

// Steam ID form (Mod CP submit)
$l['mysteam_submit_success_modcp'] = "Sukces! Steam ID użytkownika zostało połączone. Następująca informacja jest powiązana z podanym przez Ciebie kontem:";
$l['mysteam_decouple_success_modcp'] = "Steam ID zostało pomyślnie odłączone od profilu użytkownika.";
?>
