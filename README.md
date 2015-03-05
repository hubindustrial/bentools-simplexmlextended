SimpleXMLExtended
-----------

Just an enhancement of the PHP5 native SimpleXmlElement.
Adds the ability to add / replace / remove childs, with or without CDATA, and some method chainings such as dXpath() (for returning the 1st Xpath element found), etc.
Fixes the __toString() default method that does not properly convert to your encoding if you're not using UTF-8 (requires PHP 5.4+).

Installation
------------
Add the following line into your composer.json :

    {
        "require": {
            "bentools/simplexmlextended": "dev-master"
        }
    }  
Enjoy.