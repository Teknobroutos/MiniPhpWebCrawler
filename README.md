# MiniPhpWebCrawler
This is a public git Repo for a Job Qualification Mini Project

Περιγραφή:
Το project υλοποιεί ένα command-line PHP Web Crawler με χρήση της βιβλιοθήκης `simple_html_dom`, το οποίο:

- Λαμβάνει μια λίστα από URLs προϊόντων από αρχείο `urls.json`
- Ανακτά το HTML περιεχόμενο κάθε σελίδας μέσω `cURL`
- Κάνει parsing του HTML μεσω CSS Selectors και εξάγει:
  - Όνομα προϊόντος
  - Τιμή (με το σύμβολο νομίσματος)
  - Διαθεσιμότητα (π.χ. "In stock", "Out of stock")
  - Όνομα καταστήματος
  - Ημερομηνία και ώρα αποθήκευσης
- Αποθηκεύει τα αποτελέσματα σε SQLite βάση (`scrape_results.db`)
- Καταγράφει αποτυχίες (π.χ. προβλήματα προσβασιμότητας ή parsing) σε αρχείο `log.txt`
- Ξαναδοκιμάζει αποτυχημένα αιτήματα 1 ακόμα φορά πριν καταγραφεί σφάλμα

Δομή του Project:
MiniPhpWebCrawler/
  - Mini_Web_Crawler.php     : κύριο script εκτέλεσης
  - urls.json                : λίστα URLs για scraping
  - Scrape_Config.json       : βάση δεδομένων SQLite
  -scrape_results.db         : βάση δεδομένων SQLite
  -log.txt                   : αρχείο καταγραφής σφαλμάτων
  -simple_html_dom.php       : HTML parser βιβλιοθήκη
  -README.md                 : αυτό το αρχείο
  -Scrape_results.db         :Βαση δεδωμένων


Εκτέλεση
  -Απαραίτητο: PHP 8.x με ενεργοποιημένο sqlite3 και curl
Εκκίνηση του crawler:
  Command Line: php Mini_Web_Crawler.php

Έλεγχος αποτελεσμάτων:
  Παρέχω Screenshot απο GUI Περιβάλλον SQLite

Δυσκολίες που Αντιμετωπίστηκαν και παραδοχές
  JavaScript-rendered Sites:
  Προσπάθησα να εφαρμόσω scraping σε sites όπως Skroutz.gr και Amazon.com, όμως αυτά φορτώνουν δυναμικά το περιεχόμενο (τιμές/διαθεσιμότητα) μέσω JavaScript. Το simple_html_dom δεν αρκεί για parsing τέτοιων σελίδων. Λόγω απλότητας και χρόνου επέλεξα Site με πιο απλό HTML.

