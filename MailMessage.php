<?php
/**
 * MailMessage.php
 *
 * MailMessages represent actual email messages in a more OOP manner
 * than PHP's standard library supports.
 *
 * @package MailQueue
 * @author Duane Sibilly <duane@sibilly.com>
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
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
  private $_content;
  private $_subject;
  private $_recipients;
  private $_headers;
  private $_errors;
  
  public function __construct() {
    $this->reset();
  }
  
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
  
  public function reset() {
    $this->content = '';
    $this->_recipients = new MailRecipientList();
    $this->_headers = new MailHeaderList();
    $this->_errors = array();
    $this->_headers->addHeader(MailHeader::ofTypeWithContent('X-Mailer', 'MailQueue ' . VERSION));
  }
  
  public function from($address) {
    return $this->_headers->addHeader(FromHeader::withSender($address));
  }
  
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
  
  public function subject($subject = NULL) {
    if (is_null($subject))
      return $this->_subject;
    
    $this->_subject = $subject;
  }
  
  public function content($content = NULL) {
    if (is_null($content))
      return $this->_content;
    
    $this->_content = $content;
  }
  
  public function addRecipientWithNameAndAddress($name, $address) {
    try {
      return $this->addMailRecipient(MailRecipient::withNameAndAddress($name, $address));
    } catch (Exception $e) {
      echo __METHOD__ . ': ' . $e->getMessage();
      return FALSE;
    }
  }
  
  public function addRecipientWithAddress($address) {
    try {
      return $this->addMailRecipient(MailRecipient::withAddress($address));
    } catch (Exception $e) {
      echo __METHOD__ . ': ' . $e->getMessage();
      return FALSE;
    }
  }
  
  public function addMailRecipient($mailRecipient) {
    return $this->_recipients->addMailRecipient($mailRecipient);
  }
  
  public function batchSend() {
    return $this->send(TRUE);
  }
  
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
  
  public function errors() {
    return $this->_errors;
  }
}