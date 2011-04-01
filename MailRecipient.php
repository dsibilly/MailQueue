<?php
/**
 * MailRecipient.php
 *
 * Wrapper classes for email recipients and array-like lists of recipients
 *
 * Copyright 2011 Duane Sibilly. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE DUANE SIBILLY "AS IS" AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES 
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL DUANE SIBILLY BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS 
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @package MailQueue
 * @author Duane Sibilly <duane@sibilly.com>
 */

/**
 * The MailRecipient is an object wrapper for an email address (and, if
 * applicable, a recipient's name).
 *
 * @since   0.1
 */
class MailRecipient
{
  /**
   * The name associated with this recipient.
   *
   * @access private
   * @var string
   */
  private $_name;
  
  /**
   * The address associated with this recipient.
   *
   * @access private
   * @var string
   */
  private $_address;
  
  /**
   * Initialize a new, empty MailRecipient instance.
   */
  public function __construct() {
    $this->_name = NULL;
    $this->_address = '';
  }
  
  /**
   * Render the MailRecipient to an RFC 2822 compliant string.
   *
   * @link http://www.faqs.org/rfcs/rfc2822
   * @return  string      An RFC 2822 compliant email address.
   */ 
  public function __toString() {
    if (! is_null($this->_name))
      return $this->_name . ' <' . $this->_address . '>';
    return $this->_address;
  }
  
  /**
   * Render the MailRecipient to a JSON-encoded string for serialization.
   *
   * @return  string      A JSON-encoded serialization of this MailRecipient.
   */
  public function toJSON() {
    $obj = new stdClass;
    $obj->name = $this->_name;
    $obj->address = $this->_address;
    return json_encode($obj);
  }
  
  /**
   * Mixed accessor for MailRecipient's name.
   *
   * To retrieve the name, call with default arguments (e.g. name()).
   * To set the name, call with a valid string (e.g. name('John Doe')).
   */ 
  public function name($name = NULL) {
    if (is_null($name))
      return $this->_name;
    $this->_name = $name;
  }
  
  /**
   * Mixed accessor for MailRecipient's email address.
   *
   * To retrieve the name, call with default arguments (e.g. address()).
   * To set the name, call with a valid email address (e.g. address('john.doe@example.com')).
   *
   * @throws Exception if $address is not a vaild email address.
   */ 
  public function address($address = NULL) {
    if (is_null($address))
      return $this->_address;
    
    if (! self::addressIsValid($address))
      throw new Exception("$address is not a valid email address");
    else $this->_address = $address;
  }
  
  
  /**
   * Adds this MailRecipient object to the indicated MailRecipientList.
   *
   * @param MailRecipientList $mailRecipientList  A MailRecipientList.
   * @return bool TRUE if successfully added to the MailRecipientList, FALSE on failure.
   */
  public function addToMailRecipientList($mailRecipientList) {
    return $mailRecipientList->addMailRecipient($this);
  }
  
  /**
   * Adds this MailRecipient to the MailRecipientList of the indicated MailMessage.
   *
   * @param MailMessage $mailMessage A MailMessage object.
   * @return bool TRUE if successfully added to the MailMessage, FALSE on failure.
   */
  public function addToMailMessage($mailMessage) {
    return $mailMessage->addMailRecipient($this);
  }
  
  /**
   * Static factory method; creates a MailRecipient with the specified name and email address.
   *
   * @static
   * @param string $name The MailRecipient's name.
   * @param string $address The MailRecipient's email address.
   * @return MailRecipient A MailRecipient object.
   * @throws Exception if $address is not a valid email address.
   */
  public static function withNameAndAddress($name, $address) {
    try {
      $mailRecipient = new MailRecipient();
      $mailRecipient->name($name);
      $mailRecipient->address($address);
      return $mailRecipient;
    } catch (Exception $e) {
      throw $e;
    }
  }
  
  /**
   * Static factory method; creates a MailRecipient with the specified email address.
   *
   * @static
   * @param string $address The MailRecipient's email address.
   * @return MailRecipient A MailRecipient object.
   * @throws Exception if $address is not a valid email address.
   */
  public static function withAddress($address) {
    try {
      $mailRecipient = new MailRecipient();
      $mailRecipient->address($address);
      return $mailRecipient;
    } catch (Exception $e) {
      throw $e;
    }
  }
  
  /**
   * Rudimentary email address validation method.
   *
   * @static
   * @param string $address The email address to be validated.
   * @return bool TRUE if the email address is valid, FALSE if it is invalid.
   */
  public static function addressIsValid($address) {
    $isValid = TRUE;
    $atIndex = strrpos($address, "@");
    if (is_bool($atIndex) && !$atIndex)
    {
      $isValid = FALSE; // No '@' symbol found!
    }
    else
    {
      $domain = substr($address, $atIndex+1);
      $local = substr($address, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
        // local part length exceeded
        $isValid = FALSE;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
        // domain part length exceeded
        $isValid = FALSE;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
        // local part starts or ends with '.'
        $isValid = FALSE;
      }
      else if (preg_match('/\\.\\./', $local))
      {
        // local part has two consecutive dots
        $isValid = FALSE;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
        // character not valid in domain part
        $isValid = FALSE;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
        // domain part has two consecutive dots
        $isValid = FALSE;
      }
      else if (! preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
      {
        // character not valid in local part unless 
        // local part is quoted
        if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local)))
        {
          $isValid = FALSE;
        }
      }
      
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
        // domain not found in DNS
        $isValid = FALSE;
      }
    }
    return $isValid;
  }
}


/**
 * The MailRecipientList is an iterable, array-accesible data structure
 * for storing MailRecipient objects.
 *
 * @since   0.1
 */
class MailRecipientList implements Iterator, ArrayAccess
{

  /**
   * A position counter for Iterator logistics.
   *
   * @access private
   * @var int
   */
  private $_position;
  
  /**
   * An array of MailRecipient objects.
   *
   * @access private
   * @var array
   */
  private $_list;
  
  /**
   * Initialize a new, empty MailRecipientList instance.
   */
  public function __construct() {
    $this->reset();
  }
  
  /**
   * Render the MailRecipientList to an RFC 2822 compliant string.
   *
   * @link http://www.faqs.org/rfcs/rfc2822
   * @return string  An RFC 2822 compliant string of email addresses.
   */
  public function __toString() {
    $ret = '';
    for ($i = 0, $len = count($this->_list); $i < $len; $i += 1) {
      $ret .= (string)$this->_list[$i];
      if ($i < ($len - 1))
        $ret .= ', ';  
    }
    return $ret;
  }
  
  /**
   * Adds an MailRecipient object to the list (if not already present).
   *
   * @param MailRecipient $mailRecipient A MailRecipient object.
   * @return bool TRUE if the MailRecipient is successfully added, FALSE on failure.
   */
  public function addMailRecipient($mailRecipient) {
    if (! is_a($mailRecipient, 'MailRecipient'))
      return FALSE;
    
    if ($this->addressExists($mailRecipient->address()))
      return FALSE;
    
    $this->_list[] = $mailRecipient;
    return TRUE;
  }
  
  /**
   * Resets the MailRecipientList to an empty state.
   */
  public function reset() {
    $this->_list = array();
    $this->rewind();
  }
  
  /**
   * Whether an email address exists in this MailRecipientList.
   *
   * @access private
   * @param string $adddress An email address.
   * @return bool TRUE if the email address exists within this MailRecipientList, FALSE if not.
   */
  private function addressExists($address) {
    foreach($this->_list as $recip) {
      if (strcmp($address, $recip->address()) == 0)
        return TRUE;
    }
    return FALSE;
  }
  
  /**
   * Static factory method.
   *
   * @static
   * @return MailRecipientList An empty MailRecipientList.
   */
  public static function create() {
    return new MailRecipientList();
  }
  
  /**
   * Rewinds back to the first element of the Iterator.
   *
   * @see Iterator
   * @link http://www.php.net/manual/en/iterator.rewind.php
   */
  public function rewind() {
    $this->_position = 0;
  }
  
  /**
   * Returns the current element in an iteration.
   *
   * @see Iterator
   * @link http://www.php.net/manual/en/iterator.current.php
   */
  public function current() {
    return $this->_list[$this->_position];
  }
  
  /**
   * Returns the key of the current element in an iteration.
   *
   * @see Iterator
   * @link http://www.php.net/manual/en/iterator.key.php
   */
  public function key() {
    return $this->_position;
  }
  
  /**
   * Moves the current position to the next element in an iteration.
   *
   * @see Iterator
   * @link http://www.php.net/manual/en/iterator.next.php
   */
  public function next() {
    ++$this->_position;
  }
  
  /**
   * Checks if the current iterator position is valid.
   *
   * @see Iterator
   * @link http://www.php.net/manual/en/iterator.valid.php
   */
  public function valid() {
    return isset($this->_list[$this->_position]);
  }
  
  /**
   * Assigns a value to the specified offset.
   *
   * @see ArrayAccess
   * @link http://us.php.net/manual/en/arrayaccess.offsetset.php
   */
  public function offsetSet($offset, $value) {
    if (is_null($offset)) {
      $this->_list[] = $value;
    } else {
      $this->_list[$offset] = $value;
    }
  }
  
  /** 
   * Whether an offset exists.
   *
   * @see ArrayAccess
   * @link http://us.php.net/manual/en/arrayaccess.offsetexists.php
   */
  public function offsetExists($offset) {
    return isset($this->_list[$offset]);
  }
  
  /**
   * Unsets the specified offset.
   *
   * @see ArrayAccess
   * @link http://us.php.net/manual/en/arrayaccess.offsetunset.php
   */
  public function offsetUnset($offset) {
    unset($this->_list[$offset]);
  }
  
  /**
   * Retrieves the value at the specified offset.
   *
   * @see ArrayAccess
   * @link http://us.php.net/manual/en/arrayaccess.offsetget.php
   */
  public function offsetGet($offset) {
    return isset($this->_list[$offset]) ? $this->_list[$offset] : NULL;
  }
}