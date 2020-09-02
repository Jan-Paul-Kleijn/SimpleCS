<?php
/**
* Create Tables
*/
class CreateTablesDefaults {

  /**
   * @var type 
   */
  private $pdo = null;

  /**
   * Open the database connection.
   */
  public function __construct() {
    $conStr = sprintf("mysql:host=%s;dbname=%s", DB_HOST, DB_NAME);
    try {
      $this->pdo = new PDO($conStr, DB_USER, DB_PASSWORD);
    } catch (PDOException $e) {
      die($e->getMessage());
    }
  }

  /**
   * Close the database connection.
   */
  public function __destruct() {
    // Close the database connection.
    $this->pdo = null;
  }

  /**
   * Create the necessary tables for the system to work.
   * @return boolean returns true on success or false on failure.
   */
  public function createTables() {
    $sql = <<<EOSQL
      CREATE TABLE IF NOT EXISTS articles (
        id                   int(11)            NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title                varchar(100)       DEFAULT NULL,
        seftitle             varchar(100)       DEFAULT NULL,
        text                 longtext,
        date                 datetime           DEFAULT NULL,
        category             int(8)             NOT NULL DEFAULT '0',
        position             int(6)             DEFAULT NULL,
        extraid              varchar(8)         DEFAULT NULL,
        page_extra           varchar(8)         DEFAULT NULL,
        displaytitle         char(3)            NOT NULL DEFAULT 'YES',
        displayinfo          char(3)            NOT NULL DEFAULT 'YES',
        commentable          varchar(5)         NOT NULL DEFAULT '',
        published            int(3)             NOT NULL DEFAULT '1',
        description_meta     varchar(255)       DEFAULT NULL,
        keywords_meta        varchar(255)       DEFAULT NULL,
        show_on_home         enum('YES','NO')   DEFAULT 'YES',
        socialbuttons        varchar(5)         DEFAULT 'NO',
        show_in_subcats      enum('YES','NO')   DEFAULT 'NO',
        artorder             smallint(6)        NOT NULL DEFAULT '1',
        visible              varchar(6)         DEFAULT 'YES',
        default_page         varchar(6)         DEFAULT 'NO',
        mod_date             datetime           DEFAULT NULL,
        show_author          varchar(3)         DEFAULT NULL,
        author               varchar(64)        DEFAULT NULL
      );
      CREATE TABLE IF NOT EXISTS categories (
        id                     int(8)             NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name                   varchar(100)       NOT NULL,
        seftitle               varchar(100)       NOT NULL,
        description            varchar(255)       NOT NULL,
        published              varchar(4)         NOT NULL DEFAULT 'YES',
        catorder               smallint(6)        NOT NULL DEFAULT '0',
        subcat                 int(8)             NOT NULL DEFAULT '0'
      );
      CREATE TABLE IF NOT EXISTS comments (
        id                     int(11)            NOT NULL AUTO_INCREMENT PRIMARY KEY,
        articleid              int(11)            DEFAULT '0',
        name                   varchar(50)        DEFAULT NULL,
        url                    varchar(100)       NOT NULL,
        comment                text,
        time                   datetime           NOT NULL DEFAULT '0000-00-00 00:00:00',
        approved               varchar(5)         NOT NULL DEFAULT 'True'
      );
      CREATE TABLE IF NOT EXISTS extras (
        id                     int(8)             NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name                   varchar(40)        NOT NULL,
        seftitle               varchar(100)       DEFAULT NULL,
        description            varchar(100)       NOT NULL
      );
      CREATE TABLE IF NOT EXISTS settings (
        id                     int(8)             NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name                   varchar(30)        NOT NULL,
        value                  varchar(255)       NOT NULL
      );
EOSQL;
    return $this->pdo->exec($sql);
  }

  /**
   * Insert the default records in the settings table.
   */

  /**
   * Insert a single record into the settings table
   * @param string $id
   * @param string $name
   * @param string $value
   * @return mixed returns false on failure 
   */
  function insertRecord($id, $name, $value) {
    $record = array(
      ':id' => $id,
      ':name' => $name,
      ':value' => $value
    );

    $sql = <<<EOSQL
      INSERT INTO settings (
        id,
        name,
        value
      )
      VALUES (
        :id,
        :name,
        :value
      );
EOSQL;
    $query = $this->pdo->prepare($sql);
    return $query->execute($record);
  }

  function setDefaults() {
    $defaults = array(
      array(1, 'website_title', 'Simple CS'),
      array(2, 'home_sef', 'home'),
      array(3, 'website_description', 'Simple CS, voor als het eenvoudig moet.'),
      array(4, 'website_keywords', ''),
      array(5, 'website_email', ''),
      array(6, 'contact_subject', ''),
      array(7, 'language', 'NL'),
      array(8, 'charset', 'UTF-8'),
      array(9, 'date_format', 'd.m.Y.+H:i'),
      array(10, 'article_limit', '9'),
      array(11, 'rss_limit', '12'),
      array(12, 'display_page', '1'),
      array(13, 'display_new_on_home', 'on'),
      array(14, 'display_pagination', ''),
      array(15, 'num_categories', ''),
      array(16, 'show_cat_names', ''),
      array(17, 'approve_comments', ''),
      array(18, 'mail_on_comments', ''),
      array(19, 'comment_repost_timer', '15'),
      array(20, 'comments_order', ''),
      array(21, 'comment_limit', ''),
      array(22, 'enable_comments', 'NO'),
      array(23, 'freeze_comments', 'NO'),
      array(24, 'word_filter_enable', ''),
      array(25, 'word_filter_file', ''),
      array(26, 'word_filter_change', ''),
      array(27, 'username', '200ceb26807d6bf99fd6f4f0d1ca54d4'),
      array(28, 'password', '200ceb26807d6bf99fd6f4f0d1ca54d4'),
      array(29, 'enable_extras', 'NO'),
      array(30, 'last_date', '2020-08-25 13:49:23'),
      array(31, 'file_extensions', 'phps,php,txt,inc,htm,html'),
      array(32, 'allowed_files', 'php,htm,html,txt,inc,css,js,swf,jpg,jpeg,mp3'),
      array(33, 'allowed_images', 'gif,jpg,jpeg,png'),
      array(34, 'login_url', 'login'),
      array(35, 'display_blogpage', ''),
      array(36, 'overview_pagename', ''),
      array(37, 'display_social_buttons', 'on'),
      array(38, 'overview_menuname', 'overzicht'),
      array(39, 'company_contact', ''),
      array(40, 'company_phone', ''),
      array(41, 'facebook_admin', ''),
      array(42, 'disqus_shortname', ''),
      array(43, 'show_author_on', ''),
      array(44, 'previewlines', '1'),
      array(45, 'show_title_on', ''),
      array(46, 'show_agenda', ''),
      array(47, 'show_info_on', ''),
      array(48, 'show_overview_in_menu', '')
    );
    
    /**
     * Use foreach to insert values into records of the settings table
     */
    foreach($defaults as $r) {
      $this->insertRecord($r[0], $r[1], $r[2]);
    }
    
    echo "<p>Simple CS is geinstalleerd.</p>";
    echo "<p><a href=\"/\">Ga naar de homepage</a></p>";
  }
}
?>
