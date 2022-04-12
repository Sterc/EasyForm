<?php

namespace Sterc\EasyForm\Plugins;

use MODX\Revolution\Mail\modPHPMailer;
use MODX\Revolution\Mail\modMail;
use MODX\Revolution\modX;
use PHPMailer\PHPMailer\SMTP;
use xPDO\xPDO;

class Email extends BasePlugin
{
    public function onInitForm($properties)
    { 
        $this->form->setValue('name', 'Sander');
        $this->form->setValue('topic', 'General');
        $this->form->setValue('email', 'sander@sterc.nl');
        $this->form->setValue('brands', ['Audi', 'BMW']);
        $this->form->setValue('interests', ['Cars', 'Games']);

        return true;
    }

    public function onFormSubmitted($properties)
    {
        $tpl                    = $this->form->modx->getOption('tpl', $properties, '');
        $html                   = $this->form->modx->getOption('html', $properties, true);
        $convertNewlines        = $this->form->modx->getOption('convertNewlines', $properties, false);
        $multiSeparator         = $this->form->modx->getOption('multiSeparator', $properties, '\n');
        $multiWrapper           = $this->form->modx->getOption('multiWrapper', $properties, '{$values}');
        $subject                = $this->form->modx->getOption('subject', $properties, '');
        $subjectField           = $this->form->modx->getOption('subjectField', $properties, '');
        $selectEmailToAddresses = $this->form->modx->getOption('selectEmailToAddresses', $properties, []);
        $selectEmailToField     = $this->form->modx->getOption('selectEmailToField', $properties, '');
        $attachments            = $this->form->modx->getOption('attachments', $properties, null);
        $attachFilesToEmail     = (boolean) $this->form->modx->getOption('attachFilesToEmail', $properties, false);

        $message = $this->form->easyForm->getChunk($tpl, $this->prepareValues($multiWrapper, $multiSeparator));

        /* When provided, use subjectField for the email subject. */
        if (!empty($subjectField)) {
            $subject = $this->form->getValue($subjectField);
        }
        
        /* Process string to allow usage of placeholders. */
        $subject = $this->form->easyForm->getPdoFetch()->getChunk('@INLINE ' . $subject, $this->form->getValues());
        
        $mail = new modPHPMailer($this->form->modx);
        
        $mail->setHTML($html);
        $mail->set(modMail::MAIL_BODY, $html && $convertNewlines ? nl2br($message) : $message);
        $mail->set(modMail::MAIL_FROM, $this->getEmailFrom($properties));
        $mail->set(modMail::MAIL_FROM_NAME, $this->getEmailFromName($properties));
        $mail->set(modMail::MAIL_SENDER, $this->getEmailFrom($properties));
        $mail->set(modMail::MAIL_SUBJECT, $subject);

        /* Add addresses. */
        foreach (['to', 'cc', 'bcc', 'reply-to'] as $type) {
            if (!empty($properties[$type])) {
                if (is_array($properties[$type])) {
                    if (isset($properties[$type]['email'])) {
                        $mail->address($type, $properties[$type]['email'], $properties[$type]['name'] ?? '');
                    } elseif (count($properties[$type]) > 0) {
                        /* When nested array: 'to' => [['email' => 'john@doe.com', 'name' => 'John doe'],['email' => 'john2@doe.com', 'name' => 'John doe 2']]. */
                        foreach ($properties[$type] as $address) {
                            $mail->address($type, $address['email'], $address['name'] ?? '');
                        }
                    }
                } else {
                    $mail->address($type, $properties[$type], '');
                }
            }
        }

        /* When a select is used to determine email to address. */
        if (!empty($selectEmailToField) && ($selectedEmailIndex = $this->form->getValue($selectEmailToField)) && $selectedEmailIndex !== '') {
            $selectedEmailIndex = (int) $selectedEmailIndex - 1;
            if (isset($selectEmailToAddresses[$selectedEmailIndex]['email'])) {
                $mail->address('to', $selectEmailToAddresses[$selectedEmailIndex]['email'], $selectEmailToAddresses[$selectedEmailIndex]['name'] ?? '');
            }
        }

        /* Add attachments to email. */
        $this->addAttachments($mail, $attachments, $attachFilesToEmail);
        
        $sent = $mail->send();
        if (!$sent) {
            /**
             * @todo Set errors.
             */
            $this->form->addError('email', 'test');
            $this->form->modx->log(modX::LOG_LEVEL_ERROR, '[' . __CLASS__ . '] Sending email failed:  ' . print_r($mail->mailer->ErrorInfo, true));
        }

        $mail->reset([
            modMail::MAIL_CHARSET  => $this->form->modx->getOption('mail_charset', null, 'UTF-8'),
            modMail::MAIL_ENCODING => $this->form->modx->getOption('mail_encoding', null, '8bit'),
        ]);

        return $sent;
    }
    
    protected function prepareValues($multiWrapper, $multiSeparator)
    {
        $values = $this->form->getValues();
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value        = implode($multiSeparator, $value);
                $value        = str_replace(['[[+values]]', '{$values}'], $value, $multiWrapper);
                $values[$key] = $value;
            }
        }

        return $values;
    }

    protected function getEmailFrom(array $properties = [])
    {
        $from = $this->form->modx->getOption('emailsender');
        if (!empty($properties['from'])) {
            if (is_array($properties['from']) && !empty($properties['from']['email'])) {
                $from = $properties['from']['email'];
            } else {
                $from = $properties['from'];
            }
        }

        return $from;
    }

    protected function getEmailFromName(array $properties = [])
    {
        $name = $this->form->modx->getOption('site_name');
        if (!empty($properties['from'])) {
            if (is_array($properties['from']) && !empty($properties['from']['name'])) {
                $name = $properties['from']['name'];
            }
        }

        return $name;
    }

    /**
     * Add attachments to mail.
     * Either attachments attached by scriptproperties or uploaded files.
     */
    protected function addAttachments($mail, $attachments, $attachFilesToEmail = false)
    {
        if ($attachments && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                $attachment = $this->form->easyForm->preparePath($attachment);

                $mail->attach($attachment);
            }
        }

        /* Add attachments from file uploads. */
        if ($attachFilesToEmail && count($_FILES) > 0) {
            foreach ($_FILES as $file) {
                if ($file['error'] !== 0) {
                    $this->form->modx->log(xPDO::LOG_LEVEL_ERROR, sprintf('[%s] Could not add attachment due to error. File: "%s" with error code "%s".', __CLASS__, $file['name'], $file['error']));
                } else {
                    $mail->mailer->addAttachment($file['tmp_name'], $file['name'], 'base64', $file['type']);
                }
            }
        }
    }
}
