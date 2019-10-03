# EBS-project_team-1

## Getting Started

The goal of the calculator is to automatically calculate the date when the term of copyright of the work will expire and the work will be made freely available for the public. Application uses the Sierra REST API in order to send queries and get the data from the ESTER (The online catalogue ESTER is the shared catalogue of the ELNET Consortium member libraries (containing information on all documents acquired by these libraries). The catalogue also contains data on the holdings of university college libraries and special libraries.)

### Prerequisites

1. Some server where you can run .php code

2. To access Sierra, the API key and password (must be added to file rights.php)

### Installing and Integrating

Integrating to Sierra WebPAC Pro you must install additional javascript to rewrite record URL and make link to this rights.php code.

1. create link to calculator

Modify file ../screens/bib_display.html in Sierra
```
<!-- show licese button or link -->
<span id="license"  style="margin-left: 20px;"><a href="https://www.elnet.ee/estermeil/rights.php">View Rights</a></span><script>call_license();</script>
```
2. add javascript to HTML header
```
//redirect to rights script
function call_license(){
var rights = document.getElementById('license').innerHTML;
var url = location.href;
     if (document.location.href.indexOf("*est") > 0){ //url '*est'  - estonian
	var new_url = rights.replace('https://www.elnet.ee/estermeil/rights.php','https://www.elnet.ee/estermeil/rights.php?URL=' + recordnum + '&lang=et');
    }else{
	var new_url = rights.replace('https://www.elnet.ee/estermeil/rights.php','https://www.elnet.ee/estermeil/rights.php?URL='+recordnum+'&lang=en');
    }
document.getElementById('license').innerHTML = new_url;
}
```

See Ex. [https://www.ester.ee:444/record=b2699306]
(Left corner "View Rights")

## Calculator Deployment

Copy files to server

rights.php - code to calculate values

rights.css - design the webpage layout

Sierra.php - Sierra REST-API connector made by Sean Watkins <slwatkins@uh.edu>

access.log - where stored the use of this script (date/time, IP, Query, Result)
  
## Use 

(http://your.server.com/rights.php?URL=[Sierra_server_and_system_with_record_number]&debug)

Code uses URL to get ID/ISBN and 3 types of parameters
- &xml - returns XML result (for M2M)
- &debug - shows the calculation data
- &URL - integrated system ID

## Examples

1. Direct URL to working skript 

[https://www.elnet.ee/estermeil/rights.php?URL=https://www.ester.ee/record=b5243163~S1*est]

2. XML query:

[https://www.elnet.ee/estermeil/rights.php?URL=https://www.ester.ee:444/record=b1355887~S1&lang=en&xml]
XML response:
```
<?xml version="1.0" encoding="utf-8"?>
<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
<dc:creator></dc:creator>
<dc:title>Eesti elu : [Illustreerinud P. Burman]</dc:tilte>
<dc:date>1913</dc:date>
<dc:rights>Estimated copyright expiration date: 01.01.2042</dc:rights>
</metadata>
```

3. Query with debug info: 

[https://www.elnet.ee/estermeil/rights.php?URL=https://www.ester.ee:444/record=b1355887~S1&debug]

## Authors

* **Jane Makke** - *Initial work* - [jane1976](https://github.com/jane1976/EBS-project_team-1)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

