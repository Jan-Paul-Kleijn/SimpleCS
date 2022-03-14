<h1>SimpleCS</h1>
<p><em>Lightweight XHTML Content System</em></p>
<h2>About</h2>
SimpleCS is a lightweight Content System written in PHP and paired with a MySQL database. This multilingual system gives you the maximum amount of freedom for creating outrageous and extravagant websites. It features FCKeditor as content editor and produces a very efficient XHTML result.

Included is an events calendar that sports various modes of display and *a handy javascript enhanced gallery* that is coupled with the FCKeditor plugin called *Artistry*. For uploading pictures and the like, SimpleCS is equipped with *an easy to use Filemanager*. 

The blog pages and events can be expanded with comments which can be turned on or off on each page. A straightforward website contact page is standard available after installation. Further features include Clean URLs, categories & subcategories, RSS/Atom, website search and more.

<h2>Installation</h2>
The installation of SimpleCS is pretty straightforward.

* Copy all files in this repository to your web directory.
* Create a new MySQL database along with a database user and make sure that the user has all rights to use this database.
* Edit *scs-config.php* to reflect the database and database user information that you just created.
* Run *scs-install.php* to create the neccessary database tables and content.

<h2>Logging in for the first time</h2>
After you installed SimpleCS you can log in by navigating to <code>https<span>/</span>/www<span>.</span>yourdomain<span>.</span>com/login</code>, where <em>yourdomain.com</em> is a placeholder for your domain name and TLD. 

To login you can fill in your username and password, which are set both as *administrator* by default.

After you log in for the first time it's very important to change the default username and password to make sure that only you have access to the backend of your website.

<h2>Functions available</h2>

### isHomepage()

Indicates if the page shown is the homepage.

Returns `true` or `false` (bool)

### isUser()

Indicates if the current user is logged in as administrator or not.

Returns `true` or `false` (bool)

### frontendMode()

Indicates if the current page shown is the frontend or the backend.

Returns `true` or `false` (bool)

### getIdentity()

Identifies the name of the underlying page that produces the current webpage that is shown. Normally speaking this would be *index.php*.

Returns the physical page name (string)

### is404()

Indicates if the current page exists or not.

Returns `true` or `false` (bool)

### isArticle(slug)

Indicates if the page named [*slug*] is a **blogpage**.

Returns `true` or `false` (bool)

### isPage(slug)

Indicates if the page named [*slug*] is a **webpage**.

Returns `true` or `false` (bool)

### isEvent(slug)

Indicates if the page named [*slug*] is a **event page** (for the agenda).

Returns `true` or `false` (bool)

### isCategory(slug)

Indicates if the page named [*slug*] is a **blog category**.

Returns `true` or `false` (bool)

### isMainCategory(slug)

Indicates if the the page named [*slug*] is a main category of the blog.

Returns `true` or `false` (bool)

### isSubCategory(slug)

Indicates if the page named [*slug*] is a sub category of the blog

Returns `true` or `false` (bool)

### getCatIdBySEF(slug)

This function can be used to get the *id* of the category by *slug*.

Returns the category id (INT)

### getCat(categoryID)

This function can be used to get the *slug* of the category by *id*.

Returns the category slug (INT)

### s(name)

Retrieve a value from the SimpleCS settings.

Returns the setting value (string)

### l(key)

Translates a key to the language set in the SimpleCS settings.

Returns the translated value that corresponds with the provided [*key*] (string)

### tabindex(interval)

Retrieves the current tabindex. The [*interval*] value can be used to start the tabindex with this number.

Returns the (HTML) tabindex (INT)
