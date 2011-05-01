<?php
/**
 * MailMessage.php
 *
 * MailMessages represent actual email messages in a more OOP manner
 * than PHP's standard library supports.
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
 * The version of the MailQueue project.
 */
define('VERSION', 0.1);

require_once('MailRecipient.php');
require_once('MailHeader.php');

/**
 * The MailMessage is an object wrapper for an email message, complete
 * with support for multiple To:, Cc: and Bcc: recipients, customizable
 * From: addresses, additional headers and both batch and serial mailing.
 *
 * @since   0.1
 */
class MailMessage
{
  /**
   * The message content.
   *
   * @access private
   */
  private $_content;

  /**
   * The message subject.
   *
   * @access private
   */
  private $_subject;

  /**
   * A MailRecipientList of message recipients.
   *
   * @access private
   */
  private $_recipients;

  /**
   * A MailHeaderList of message headers.
   *
   * @access private
   */
  private $_headers;

  /**
   * An array of error messages.
   *
   * @access private
   */
  private $_errors;

  /**
   * Initialize a new, empty MailMessage instance.
   */
  public function __construct() {
    $this->reset();
  }

  /**
   * Render the MailMessage to a formatted string.
   *
   * @return string A formatted string of the MailMessage contents.
   */
  public function __toString() {
    $ret  = '';
    $from = $this->_headers->headerType('From');
    $to   = (string)$this->_recipients;
    if ($cc = $this->_headers->headerType('Cc')) {
      $cc = (string)$cc;
    }
    if ($bcc = $this->_headers->headerType('Bcc')) {
      $bcc = (string)$bcc;
    }
    $ret .= "$from\n";
    $ret .= "To: $to\n";
    if ($cc) $ret .= "$cc\n";
    if ($bcc) $ret .= "$bcc\n";
    $ret .= $this->_content;
    $ret .= "\n\n";
    return $ret;
  }

  /**
   * Reset the MailMessage to an empty state.
   */
  public function reset() {
    $this->content = '';
    $this->_recipients = new MailRecipientList();
    $this->_headers = new MailHeaderList();
    $this->_errors = array();
    $this->_headers->addHeader(MailHeader::ofTypeWithContent('X-Mailer', 'MailQueue ' . VERSION));
  }

  /**
   * Accessor for the From: header.
   *
   * Retrieve with default arguments (e.g. from()).
   * Set with a valid email address (e..g from('john.doe@example.com')).
   */
  public function from($address = NULL) {
    if (is_null($address))
      return $this->_headers->headerType('From');
    return $this->_headers->addHeader(FromHeader::withSender($address));
  }

  /**
   * Accessor for the To: header.
   *
   * Retreive with default arguments (e.g. to()).
   * Set with a MailRecipientList (e.g. to($mailRecipientList)).
   */
  public function to($mailRecipientList = NULL) {
    if (is_null($mailRecipientList))
      return $this->_recipients;

    if (! is_a($mailRecipientList, 'MailRecipientList')) {
      $type = typeof($mailRecipientList);
      if ($type == 'object') {
        $type = get_class($mailRecipientList);
      }
      throw new Exception(__METHOD__ . " requires as an argument a MailRecipientList; $type encountered");
    }

    $this->_recipients = $mailRecipientList;
  }

  /**
   * Accessor for the Cc: header.
   *
   * Retrieve with default arguments (e.g. cc()).
   * Set with a MailRecipientList (e.g. cc($mailRecipientList)).
   */
  public function cc($mailRecipientList = NULL) {
    if (is_null($mailRecipientList))
      return $this->_headers->headerType('Cc');

    if (! is_a($mailRecipientList, 'MailRecipientList')) {
      $type = typeof($mailRecipientList);
      if ($type == 'object') {
        $type = get_class($mailRecipientList);
      }
      throw new Exception(__METHOD__ . " requires as an argument a MailRecipientList; $type encountered");
    }

    if (! $this->_headers->addHeader(CCHeader::withRecipientList($mailRecipientList))) {
      throw new Exception(__METHOD__ . " Unable to add a second CC header");
    }
  }

  /**
   * Accessor for the Bcc: header.
   *
   * Retrieve with default arguments (e.g. bcc()).
   * Set with a MailRecipientList (e.g. bcc($mailRecipientList)).
   */
  public function bcc($mailRecipientList = NULL) {
    if (is_null($mailRecipientList))
      return $this->_headers->headerType('Bcc');

    if (! is_a($mailRecipientList, 'MailRecipientList')) {
      $type = typeof($mailRecipientList);
      if ($type == 'object') {
        $type = get_class($mailRecipientList);
      }
      throw new Exception(__METHOD__ . " requires as an argument a MailRecipientList; $type encountered");
    }

    if (! $this->_headers->addHeader(BCCHeader::withRecipientList($mailRecipientList))) {
      throw new Exception(__METHOD__ . " Unable to add a second BCC header");
    }
  }

  /**
   * Accessor for the subject line.
   *
   * Retrieve with default arguments (e.g. subject()).
   * Set with a string (e.g. subject('This is the subject line')).
   */
  public function subject($subject = NULL) {
    if (is_null($subject))
      return $this->_subject;

    $this->_subject = $subject;
  }

  /**
   * Accessor for the message body.
   *
   * Retreive with default arguments (e.g. content()).
   * Set with a string (e.g. content('This is the message body')).
   */
  public function content($content = NULL) {
    if (is_null($content))
      return $this->_content;

    $this->_content = $content;
  }

  /**
   * Add a recipient to the message.
   *
   * @param string $name The recipient's name.
   * @param string $address The recipient's email address.
   * @return bool TRUE if $address is valid and not already present in the recipient list, FALSE on failure.
   */
  public function addRecipientWithNameAndAddress($name, $address) {
    try {
      return $this->addMailRecipient(MailRecipient::withNameAndAddress($name, $address));
    } catch (Exception $e) {
      echo __METHOD__ . ': ' . $e->getMessage();
      return FALSE;
    }
  }

  /**
   * Add a recipient to the message.
   *
   * @param string $address The recipient's email address.
   * @return bool TRUE if $address is valid, and not already present in the recipient list, FALSE on failure.
   */
  public function addRecipientWithAddress($address) {
    try {
      return $this->addMailRecipient(MailRecipient::withAddress($address));
    } catch (Exception $e) {
      echo __METHOD__ . ': ' . $e->getMessage();
      return FALSE;
    }
  }

  /**
   * Add a MailRecipient instance to the recipient list.
   *
   * @param MailRecipient $mailRecipient A MailRecipient instance.
   * @return bool TRUE if successful, FALSE if the MailRecipient's email address is already present in the recipient list.
   */
  public function addMailRecipient($mailRecipient) {
    return $this->_recipients->addMailRecipient($mailRecipient);
  }

  /**
   * Send the MailMessage to the registered recipients.
   *
   * @param bool $batch Set to TRUE to send one email with all To: recipients on one line (default: FALSE)
   * @return int The number of errors encountered while sending; 0 if all send operations were successful.
   */
  public function send($batch = FALSE) {
    $this->_errors = array();
    $headers = (string)$this->_headers;
    if ($batch) {
      $to = (string)$this->_recipients;
      return mail($to, $this->_subject, $this->_content, $headers);
    }

    foreach ($this->_recipients as $to) {
      if (! mail((string)$to, $this->_subject, $this->_content, $headers))
        $this->_errors[] = "Unable to send to $to";
    }

    return count($this->_errors);
  }

  /**
   * Convenience method for sending a single batch email.
   *
   * @return int The number of errors encountered while sending; 0 if all send operations were successful.
   */
  public function batchSend() {
    return $this->send(TRUE);
  }

  /**
   * Accessor for the MailMessage error array.
   *
   * @return array An array of error messages from the last send() call.
   */
  public function errors() {
    return $this->_errors;
  }
}
