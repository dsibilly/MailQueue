<?php

require_once('MailRecipient.php');

class MailHeader
{
  protected $_type;
  protected $_content;
  
  public function __construct() {
    $this->_type = '';
    $this->__content = '';
  }
  
  public function __toString() {
    return $this->_type . ': ' . $this->_content;
  }
  
  public function type($type = NULL) {
    if (! is_null($type)) {
      $this->_type = $type;
      return TRUE;
    }
    return $this->_type;
  }
  
  public function content($content = NULL) {
    if (! is_null($content)) {
      $this->_content = $content;
      return TRUE;
    }
    return $this->_content;
  }
  
  public static function ofTypeWithContent($type, $content) {
    $header = new MailHeader();
    $header->type($type);
    $header->content($content);
    return $header;
  }
}

class FromHeader extends MailHeader
{
  public function __construct() {
    parent::__construct();
    $this->_type = 'From';
  }
  
  public static function withSender($sender) {
    if (! MailRecipient::addressIsValid($sender))
      throw new Exception("$sender is not a valid email address");
      
    $header = new FromHeader();
    $header->content($sender);
    return $header;
  }
}

class ReplyToHeader extends MailHeader
{
  public function __construct() {
    parent::__construct();
    $this->_type = 'Reply-To';
  }
  
  public static function withAddress($address) {
    $header = new ReplyToHeader();
    $header->content($address);
    return $header;
  }
}

class CCHeader extends MailHeader
{
  protected $_recipients;
  
  public function __construct() {
    parent::__construct();
    $this->_type = 'Cc';
    $this->_recipients = new MailRecipientList();
  }
  
  public function __toString() {
    $ret = $this->_type . ': ';
    for ($i = 0, $len = count($this->_recipients); $i < $len; $i += 1) {
      $ret .= (string)$this->_recipients[$i];
      
      if ($i < ($len - 1))
        $ret .= ', ';
    }
    return $ret;
  }
  
  public function addRecipientByAddress($address) {
    return $this->addRecipient(MailRecipient::withAddress($address));
  }
  
  public function addRecipientByNameAndAddress($name, $address) {
    return $this->addRecipient(MailRecipient::withNameAndAddress($name, $address));
  }
  
  public function addRecipient($mailRecipient) {
    return $this->_recipients->addMailRecipient($mailRecipient);
  }
  
  public static function withRecipientList($mailRecipientList) {
    $header = new CCHeader();
    foreach ($mailRecipientList as $recip) {
      $header->addRecipient($recip);
    }
    return $header;
  }
}

class BCCHeader extends CCHeader
{
  public function __construct() {
    parent::__construct();
    $this->_type = 'Bcc';
  }
  
  public static function withRecipientList($mailRecipientList) {
    $header = new BCCHeader();
    foreach ($mailRecipientList as $recip) {
      $header->addRecipient($recip);
    }
    return $header;
  }
}

class MailHeaderList implements Iterator, ArrayAccess
{
  protected $_posititon;
  protected $_list;
  
  public function __construct() {
    $this->reset();
  }
  
  public function __toString() {
    $ret = '';
    for ($i = 0, $len = count($this->_list); $i < $len; $i += 1) {
      $ret .= (string)$this->_list[$i];
      if ($i < ($len - 1))
        $ret .= "\r\n";
    }
    return $ret;
  }
  
  public function rewind() {
    $this->_position = 0;
  }
  
  public function current() {
    return $this->_list[$this->_position];
  }
  
  public function key() {
    return $this->_position;
  }
  
  public function next() {
    ++$this->_position;
  }
  
  public function valid() {
    return isset($this->_list[$this->_position]);
  }
  
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->_list[] = $value;
    } else {
      $this->_list[$offset] = $value;
    }
  }
  
  public function offsetExists($offset) {
    return isset($this->_list[$offset]);
  }
  
  public function offsetUnset($offset) {
    unset($this->_list[$offset]);
  }
  
  public function offsetGet($offset) {
    return isset($this->_list[$offset]) ? $this->_list[$offset] : NULL;
  }
  
  public function addHeader($mailHeader) {
    if (! is_a($mailHeader, 'MailHeader'))
      return FALSE;
    
    if ($this->headerTypeExists($mailHeader->type()))
      return FALSE;
    
    $this->_list[] = $mailHeader;
    return TRUE;
  }
  
  public function reset() {
    $this->_list = array();
    $this->rewind();
  }
  
  public function headerType($type) {
    foreach ($this->_list as $header) {
      if (strcmp($type, $header->type()) == 0)
        return $header;
    }
    return FALSE;
  }
  
  private function headerTypeExists($type) {
    if ($this->headerType($type))
      return TRUE;
    return FALSE;
  }
  
  public static function create() {
    return new MailHeaderList();
  }
}