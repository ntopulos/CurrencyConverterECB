CurrencyConverterECB
====================

PHP class that allows to access currency exchange rates published by the European Central Bank (ECB).

All exchange rates are relative to the EUR.

This script was made for an existing project therefore you could want to change the design of the associated MySQL table and adapt the code accordingly.



Usage
-----

MySQL table is needed to store the exchange rates:

```mysql
CREATE TABLE IF NOT EXISTS `currency_exchange` (
  `ex_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exchange_rate_date` date NOT NULL,
  `EUR` float NOT NULL,
  `USD` float NOT NULL,
  `JPY` float NOT NULL,
  `GBP` float NOT NULL,
  `CHF` float NOT NULL,
  PRIMARY KEY (`ex_id`),
  UNIQUE KEY `exchange_rate_date` (`exchange_rate_date`)
)
```

Create a new object, it will check the currency rates online and if needed update your database.

```php
$CurrencyConverterECB = new CurrencyConverterECB($table_name, $mysqli);
// assuming $mysqli is a mysqli connection
```

Converting:
```php
$CurrencyConverterECB->convert(1,'USD','EUR');
```

You can also access the echange rates array:
```php
$CurrencyConverterECB->exchange_rates;
```

References
----------

European Central Bank exchange rates:

http://www.ecb.europa.eu/stats/exchange/eurofxref/html/index.en.html

This code was inspired by the work of Simon Jarvis:

http://www.white-hat-web-design.co.uk/blog/php-currency-conversion-exchange-rates-xml/