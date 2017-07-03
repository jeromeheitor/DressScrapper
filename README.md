# DressScrapper
Dress scrapper for Adidas Shoes 

I) Setup:

If you encounter a DOMDocument related error on your terminal, please install the following package:

$ sudo apt-get install php-xml

Now let's get started !

First set up your user’s database credentials located in the file app/parameters.yml

Then if your credentials are correct symfony should be able to create the database and it schema:
At the root of the project:
(I recommend creating an alias for « php bin/console » to avoid repeating it each time):

sf doctrine:database:create

sf doctrine:schema:create

else

php bin/console doctrine:database:create

php bin/console doctrine:schema:create

####
(Make sure your mysql user had grant access to ‘CREATE’ a database)

GRANT CREATE ON * . * TO 'yourusername'@'localhost';

GRANT ALL PRIVILEGES ON * . * TO 'yourusername'@'localhost';

FLUSH PRIVILEGES;

quit;

####

Then you can start the server:

php bin/console server:run 

II) App:

This Symfony Web based app scrapp the 5 first pages of the shoes catalogue from adidas.fr website.

You can choose both female and male sections.

All the main informations you will need will be provided in real time and printed at the homepage.

ProductId, Name, Current Price, Base Price, Current Discount.

You have the possibility to fetch these datas with your local database by simply clicking on the left button "Update Database".

DressScrapper is open source and was coded with pleasure, feel free to contribute to it.
