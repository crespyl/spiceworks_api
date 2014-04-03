##SPICEWORKS UNOFFICIAL EXTERNAL API
This is a small script to authenticate a user to Spiceworks and then fetch some
JSON API data from their Internal JSON API

It is very simple procedural code that you may want to re-work for your own purposes
I'm going to turn this into a class someday.

Here's the blog post introducing and explaining this script:
http://mediarealm.com.au/articles/2013/01/spiceworks-external-json-api-getting-started/

Caution: This may break in the future if Spiceworks changes the way they authenticate.

###Usage
```php
$url    = "https://spiceworks.example.com/";
$email  = "spiceworks-reporter@spiceworks.example.com";
$passwd = "passwd";

$cookiejar = "./cookies.txt";

$spiceworks = new Spiceworks($url, $email, $passwd, $cookiejar);

$report_json = $spiceworks->getURL("reports/show/{{ID of some report}}.json");

$report = json_decode($report_json, true);

var_dump($report);

```

Bear in mind that the `cookies.txt` file needs to be both readable and writeable by your server process, and the account you use to log in must have permission to access whatever urls you request.

In my own use-case, I created a "reports" user in Spiceworks and gave them access to a handful of reports that can generate the data I need.  You can get the url of the report data file by logging in as your reporting user and visitng that report; the url in your browser should be something like `https://spiceworks.example.com/reports/show/42`.  If the report has a graph widget associated with it, you may be able to log an AJAX request to `https://spiceworks.example.com/reports/show/42.xml`.  Note that the xml url is NOT preceded by any references to `/api/` or similar.  You can simply exchange the `xml` extension with `json` or `csv` according to your needs.


####Notices and Stuffs

Version: 2

Copyright (c) 2014, Ambassador Enterprises http://ambassador-enterprises.com/

Original Version Copyright (c) 2012, Media Realm http://mediarealm.com.au/

Based on source from https://github.com/anthonyeden/spiceworks_api

All rights reserved.


------------------------------------------------------------------------------------------

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list
  of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice, this list
  of conditions and the following disclaimer in the documentation and/or other materials
  provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
OF SUCH DAMAGE.

------------------------------------------------------------------------------------------
