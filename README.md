# InvoicePlane e-invoices config and templates

TLDR; Download the [zip], adapt the files you need and place them in their respective folders.

<details><summary>English</summary>

This repository lists and provides functional examples so that you can implement your e-invoice configurations and models with [InvoicePlane]

To add these templates to InvoicePlane:

Download the [zip] archive, modify the files you need and place them in their respective folders.

+ 🛈 Check the [generator] that the [configuration][configs] uses (name of the template file without `Xml.php`)
+ ⚠ The `Facturxv10Xml.php` and `Ublv24Xml.php` [templates] need the [`BaseXml.php`][BaseXml] file (they are extended)

You'll find more information in this [readme] from the [InvoicePlane] [repository].

</details>

<details><summary>Français</summary>

Ce répertoire recense et propose des exemples de configurations et modèles de facture électronique fonctionnels adaptés à [InvoicePlane]

Pour ajouter ces modèles a InvoicePlane:

Télécharger l'archive [zip], modifiez les fichiers dont vous avez besoins puis placez les dans leurs dossier respectifs.

+ 🛈 Veillez au [générateur][generator] que la [configuration][configs] utilise (nom du fichier modèle sans `Xml.php`)
+ ⚠ Les [modèles][templates] `Facturxv10Xml.php` et `Ublv24Xml.php` ont besoins du fichier [`BaseXml.php`][BaseXml] (ils en sont étendu)

Vous trouverez plus d'informations dans ce [readme] (en anglais) du [dépôt][repository] d'[InvoicePlane].

</details>

<details><summary>Deutsch</summary>

Dieses Verzeichnis enthält Beispiele für Konfigurationen und Vorlagen für funktionale elektronische Rechnungen, die für [InvoicePlane] geeignet sind.

Um diese Vorlagen zu InvoicePlane hinzuzufügen:

Laden Sie das [zip]-Archiv herunter, bearbeiten Sie die benötigten Dateien und legen Sie sie in ihren jeweiligen Ordnern ab.

+ 🛈 Achten Sie auf den [Generator], den die [Konfiguration][configs] verwendet (Name der Vorlagendatei ohne `Xml.php`).
+ ⚠ Die [Vorlagen][templates] `Facturxv10Xml.php` und `Ublv24Xml.php` benötigen die Datei [`BaseXml.php`][BaseXml] (sie werden um diese erweitert).

Weitere Informationen finden Sie in diesem [readme] (auf Englisch) des [InvoicePlane] [repository].

</details>

<details><summary>Español</summary>

Este directorio contiene ejemplos de configuraciones funcionales de facturas electrónicas y plantillas adaptadas a [InvoicePlane].

Para agregar estas plantillas a InvoicePlane:

Descargue el archivo [zip], modifique los archivos que necesite y colóquelos en sus respectivas carpetas.

+ 🛈 Comprueba el [generador][generator] que utiliza la [configuración][configs] (nombre del fichero de plantilla sin `Xml.php`).
+ ⚠ Las [plantillas][templates] `Facturxv10Xml.php` y `Ublv24Xml.php` necesitan el archivo [`BaseXml.php`][BaseXml] (están extendidas)

Encontrará más información en este [readme] (Ingles) del [repositorio][repository] de [InvoicePlane].

</details>

<details><summary>Italiano</summary>

Questa directory contiene esempi di configurazioni e modelli di fattura elettronica funzionali adattati a [InvoicePlane].

Per aggiungere questi modelli a InvoicePlane:

Scaricare l'archivio [zip], modificare i file necessari e inserirli nelle rispettive cartelle.

+ 🛈 Controllare il [generatore][generator] che la [configurazione][configs] utilizza (nome del file del template senza `Xml.php`)
+ ⚠ I [template][templates] `Facturxv10Xml.php` e `Ublv24Xml.php` hanno bisogno del file [`BaseXml.php`][BaseXml] (sono estesi)

Ulteriori informazioni sono contenute in questo [readme] del [repository] di [InvoicePlane].

</details>

<details><summary>Português</summary>

Este repositório enumera e propõe exemplos de configurações e modelos funcionais de facturas electrónicas adaptados ao [InvoicePlane].

Para adicionar estes modelos ao InvoicePlane:

Descarregue o arquivo [zip], modifique os ficheiros necessários e coloque-os nas respectivas pastas.

+ 🛈 Verificar o [gerador][generator] que a [configuração][configs] utiliza (nome do ficheiro do modelo sem `Xml.php`)
+ ⚠ Os [templates] `Facturxv10Xml.php` e `Ublv24Xml.php` necessitam do ficheiro [`BaseXml.php`][BaseXml] (são estendidos)

Encontrará mais informações neste [readme] do [repositório][repository] do [InvoicePlane].

</details>

<details><summary>Polski</summary>

Ten katalog zawiera przykłady funkcjonalnych konfiguracji faktur elektronicznych i szablonów odpowiednich dla [InvoicePlane].

Aby dodać te szablony do InvoicePlane:

Pobierz archiwum [zip], zmodyfikuj potrzebne pliki i umieść je w odpowiednich folderach.

+ 🛈 Sprawdź [generator], którego używa [konfiguracja][configs] (nazwa pliku szablonu bez `Xml.php`).
+ ⚠ Szablony `Facturxv10Xml.php` i `Ublv24Xml.php` wymagają pliku [`BaseXml.php`][BaseXml] (są rozszerzone).

Więcej informacji znajdziesz w tym [readme] z [InvoicePlane] [repozytorium][repository].

</details>

<details><summary>Română</summary>

Acest director enumeră și propune exemple de configurații și șabloane funcționale de facturi electronice adaptate la [InvoicePlane].

Pentru a adăuga aceste șabloane la InvoicePlane:

Descărcați arhiva [zip], modificați fișierele de care aveți nevoie și apoi plasați-le în folderele respective.

+ 🛈 Verificați [generatorul][generator] pe care îl utilizează configurația (numele fișierului șablon fără `Xml.php`)
+ ⚠ [Șabloanele][templates] `Facturxv10Xml.php` și `Ublv24Xml.php` au nevoie de fișierul [`BaseXml.php`][BaseXml] (sunt extinse)

Veți găsi mai multe informații în acest [readme] din [InvoicePlane] [repository].

</details>

[zip]: https://github.com/InvoicePlane/InvoicePlane-e-invoices/archive/refs/heads/development.zip
[readme]: https://github.com/InvoicePlane/InvoicePlane/blob/development/application/helpers/XMLconfigs/README.md
[configs]: /application/helpers/XMLconfigs
[BaseXml]: /application/libraries/XMLtemplates/Facturxv10Xml.php#L22
[generator]: /application/helpers/XMLconfigs/Zugferdv23.php#L11
[templates]: /application/libraries/XMLtemplates
[repository]: https://github.com/InvoicePlane/InvoicePlane
[InvoicePlane]: https://invoiceplane.com