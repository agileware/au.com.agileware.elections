<?php

use CRM_Elections_ExtensionUtil as E;

/**
 * How the results page looks: a logo, header text and footer text, and the celebration effect shown for a few
 * days after the results are published. These are display settings only. They are shared by the Display tab of
 * the election form and the Results page form, which can be used after the election has started.
 */
class CRM_Elections_Helper_ResultsDisplay {

  const EFFECT_CONFETTI = 'confetti';

  const EFFECT_FIREWORKS = 'fireworks';

  const EFFECT_BALLOONS = 'balloons';

  const EFFECT_SPARKLE = 'sparkle';

  const EFFECT_TICKER = 'ticker';

  const EFFECT_SURPRISE = 'surprise';

  const HEADER_MAX = 200;

  const FOOTER_MAX = 1000;

  const DAYS_DEFAULT = 5;

  const DAYS_MAX = 30;

  const LOGO_MAX_BYTES = 2097152;

  const LOGO_MAX_PIXELS = 4000;

  const LOGO_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp'];

  /**
   * The columns this class owns.
   */
  /**
   * Only tests set this: a file that was not sent in an HTTP upload is otherwise refused.
   *
   * @var bool
   */
  public static $acceptAnyFile = FALSE;

  const FIELDS = ['results_header_text', 'results_footer_text', 'results_logo_alt', 'celebration_enabled', 'celebration_effect', 'celebration_days'];

  /**
   * @return string[]
   */
  public static function getEffects() {
    return [
      self::EFFECT_CONFETTI => E::ts('Confetti'),
      self::EFFECT_FIREWORKS => E::ts('Fireworks'),
      self::EFFECT_BALLOONS => E::ts('Balloons'),
      self::EFFECT_SPARKLE => E::ts('Golden sparkle'),
      self::EFFECT_TICKER => E::ts('Ticker tape and spotlight'),
      self::EFFECT_SURPRISE => E::ts('Surprise me (a different effect for each election)'),
    ];
  }

  /**
   * Add the form elements, on the election form and on the Results page form.
   *
   * @param CRM_Core_Form $form
   */
  public static function addFormElements($form) {
    $form->add('text', 'results_header_text', E::ts('Header text'), ['size' => 60, 'maxlength' => self::HEADER_MAX]);
    $form->add('textarea', 'results_footer_text', E::ts('Footer text'), ['cols' => 60, 'rows' => 4, 'maxlength' => self::FOOTER_MAX]);
    $form->add('file', 'results_logo', E::ts('Header logo'), ['accept' => implode(',', array_keys(self::LOGO_TYPES))]);
    $form->add('text', 'results_logo_alt', E::ts('Logo description'), ['size' => 60, 'maxlength' => 255]);
    $form->add('advcheckbox', 'results_remove_logo', E::ts('Remove the logo'));
    $form->add('advcheckbox', 'celebration_enabled', E::ts('Display celebration effects on the Election results page'));
    $form->add('select', 'celebration_effect', E::ts('Celebration effect'), self::getEffects());
    $form->add('text', 'celebration_days', E::ts('Celebration lasts (days)'), ['size' => 4, 'maxlength' => 2, 'inputmode' => 'numeric']);
    $form->addFormRule(['CRM_Elections_Helper_ResultsDisplay', 'validateForm']);
  }

  /**
   * CiviCRM's API writes < and > in a text field as &lt; and &gt;. Turn them back to what the person typed, so
   * that a page or a form can escape the text once, when it is shown.
   *
   * @param string|NULL $text
   *
   * @return string
   */
  public static function decode($text) {
    return str_replace(['&lt;', '&gt;'], ['<', '>'], (string) $text);
  }

  /**
   * @param CRM_Elections_BAO_Election|NULL $election
   *
   * @return array
   */
  public static function getDefaults($election = NULL) {
    return [
      'results_header_text' => self::decode($election->results_header_text ?? ''),
      'results_footer_text' => self::decode($election->results_footer_text ?? ''),
      'results_logo_alt' => self::decode($election->results_logo_alt ?? ''),
      'results_remove_logo' => 0,
      'celebration_enabled' => (int) ($election->celebration_enabled ?? 0),
      'celebration_effect' => ($election->celebration_effect ?? '') ?: self::EFFECT_CONFETTI,
      'celebration_days' => ($election->celebration_days ?? NULL) === NULL ? self::DAYS_DEFAULT : (int) $election->celebration_days,
    ];
  }

  /**
   * The problems with what was entered, as a form rule.
   *
   * @param array $values
   *
   * @return array|bool
   */
  public static function validateForm($values) {
    $errors = [];
    if (mb_strlen((string) ($values['results_header_text'] ?? '')) > self::HEADER_MAX) {
      $errors['results_header_text'] = E::ts('The header text can be up to %1 characters.', [1 => self::HEADER_MAX]);
    }
    if (mb_strlen((string) ($values['results_footer_text'] ?? '')) > self::FOOTER_MAX) {
      $errors['results_footer_text'] = E::ts('The footer text can be up to %1 characters.', [1 => self::FOOTER_MAX]);
    }
    $days = trim((string) ($values['celebration_days'] ?? ''));
    if ($days !== '' && (!ctype_digit($days) || (int) $days < 1 || (int) $days > self::DAYS_MAX)) {
      $errors['celebration_days'] = E::ts('Enter a whole number of days from 1 to %1.', [1 => self::DAYS_MAX]);
    }
    if (!empty($values['celebration_effect']) && !array_key_exists($values['celebration_effect'], self::getEffects())) {
      $errors['celebration_effect'] = E::ts('Choose one of the effects.');
    }
    $upload = self::upload();
    if ($upload) {
      $problem = self::checkUpload($upload);
      if ($problem) {
        $errors['results_logo'] = $problem;
      }
      elseif (trim((string) ($values['results_logo_alt'] ?? '')) === '') {
        $errors['results_logo_alt'] = E::ts('Describe the logo for people who cannot see it.');
      }
    }
    return $errors ?: TRUE;
  }

  /**
   * The columns to save from the submitted values.
   *
   * @param array $values
   *
   * @return array
   */
  public static function getValuesToSave(array $values) {
    $days = trim((string) ($values['celebration_days'] ?? ''));
    return [
      'results_header_text' => trim((string) ($values['results_header_text'] ?? '')) ?: NULL,
      'results_footer_text' => trim((string) ($values['results_footer_text'] ?? '')) ?: NULL,
      'results_logo_alt' => trim((string) ($values['results_logo_alt'] ?? '')) ?: NULL,
      'celebration_enabled' => !empty($values['celebration_enabled']) ? 1 : 0,
      'celebration_effect' => ($values['celebration_effect'] ?? '') ?: NULL,
      'celebration_days' => $days === '' ? self::DAYS_DEFAULT : (int) $days,
    ];
  }

  /**
   * The file sent with the form, if one was.
   *
   * @return array|NULL
   */
  public static function upload() {
    $file = $_FILES['results_logo'] ?? NULL;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      return NULL;
    }
    return $file;
  }

  /**
   * @param array $file
   *   An entry of $_FILES.
   *
   * @return string|NULL
   *   What is wrong with it.
   */
  public static function checkUpload(array $file) {
    $received = ($file['error'] ?? 0) === UPLOAD_ERR_OK && !empty($file['tmp_name']) && (is_uploaded_file($file['tmp_name']) || self::$acceptAnyFile);
    if (!$received) {
      return E::ts('The logo could not be uploaded. Try a smaller file.');
    }
    if (filesize($file['tmp_name']) > self::LOGO_MAX_BYTES) {
      return E::ts('The logo can be up to %1 MB.', [1 => self::LOGO_MAX_BYTES / 1048576]);
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset(self::LOGO_TYPES[$info['mime']])) {
      return E::ts('The logo must be a PNG, JPEG, GIF or WebP picture.');
    }
    if ($info[0] > self::LOGO_MAX_PIXELS || $info[1] > self::LOGO_MAX_PIXELS) {
      return E::ts('The logo can be up to %1 by %1 pixels.', [1 => self::LOGO_MAX_PIXELS]);
    }
    if (!function_exists('imagecreatefromstring')) {
      return E::ts('This server cannot process pictures, so a logo cannot be uploaded.');
    }
    return NULL;
  }

  /**
   * Replace or remove the logo after the election was saved.
   *
   * The picture is read and written again, which removes anything hidden in the file (metadata, scripts).
   *
   * @param int $electionId
   * @param array $values
   *   The submitted form values.
   * @param array|NULL $file
   *   The upload, when there is one.
   *
   * @throws CRM_Core_Exception
   */
  public static function saveLogo($electionId, array $values, $file = NULL) {
    $oldId = (int) CRM_Core_DAO::singleValueQuery('SELECT results_logo_file_id FROM civicrm_election WHERE id = %1', [1 => [(int) $electionId, 'Integer']]);
    $newId = NULL;
    if ($file) {
      $problem = self::checkUpload($file);
      if ($problem) {
        throw new CRM_Elections_Exception($problem);
      }
      $newId = self::storePicture(file_get_contents($file['tmp_name']), (int) $electionId);
    }
    elseif (!empty($values['results_remove_logo']) && $oldId) {
      $newId = 0;
    }
    if ($newId === NULL) {
      return;
    }
    \Civi\Api4\Election::update(FALSE)
      ->addWhere('id', '=', (int) $electionId)
      ->addValue('results_logo_file_id', $newId ?: NULL)
      ->addValue('results_logo_alt', $newId === 0 ? NULL : ($values['results_logo_alt'] ?? NULL))
      ->execute();
    if ($oldId) {
      self::deleteFile($oldId);
    }
  }

  /**
   * Draw the picture again and keep it in CiviCRM's file store.
   *
   * @param string $bytes
   * @param int $electionId
   *
   * @return int
   *   The id of the civicrm_file row.
   */
  private static function storePicture($bytes, $electionId) {
    $info = getimagesizefromstring($bytes);
    $image = @imagecreatefromstring($bytes);
    if (!$info || !$image || !isset(self::LOGO_TYPES[$info['mime']])) {
      throw new CRM_Elections_Exception(E::ts('The logo must be a PNG, JPEG, GIF or WebP picture.'));
    }
    $extension = self::LOGO_TYPES[$info['mime']];
    $name = 'election_logo_' . (int) $electionId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $path = CRM_Core_Config::singleton()->customFileUploadDir . $name;
    imagealphablending($image, FALSE);
    imagesavealpha($image, TRUE);
    $written = FALSE;
    switch ($extension) {
      case 'png':
        $written = imagepng($image, $path);
        break;

      case 'jpg':
        $written = imagejpeg($image, $path, 90);
        break;

      case 'gif':
        $written = imagegif($image, $path);
        break;

      case 'webp':
        $written = imagewebp($image, $path, 90);
        break;
    }
    imagedestroy($image);
    if (!$written) {
      throw new CRM_Elections_Exception(E::ts('The logo could not be saved.'));
    }
    $file = new CRM_Core_DAO_File();
    $file->mime_type = $info['mime'];
    $file->uri = $name;
    $file->upload_date = date('YmdHis');
    $file->save();
    return (int) $file->id;
  }

  /**
   * @param int $fileId
   */
  public static function deleteFile($fileId) {
    $file = new CRM_Core_DAO_File();
    $file->id = (int) $fileId;
    if ($file->find(TRUE)) {
      $path = CRM_Core_Config::singleton()->customFileUploadDir . basename((string) $file->uri);
      if (is_file($path)) {
        @unlink($path);
      }
      $file->delete();
    }
  }

  /**
   * A copy of a logo for a duplicated election.
   *
   * @param int $fileId
   * @param int $electionId
   *
   * @return int|NULL
   */
  public static function copyLogo($fileId, $electionId) {
    $path = self::logoPath($fileId);
    if (!$path) {
      return NULL;
    }
    return self::storePicture(file_get_contents($path), (int) $electionId);
  }

  /**
   * @param int $fileId
   *
   * @return string|NULL
   */
  public static function logoPath($fileId) {
    $uri = CRM_Core_DAO::singleValueQuery('SELECT uri FROM civicrm_file WHERE id = %1', [1 => [(int) $fileId, 'Integer']]);
    if (!$uri) {
      return NULL;
    }
    $path = CRM_Core_Config::singleton()->customFileUploadDir . basename($uri);
    return is_file($path) ? $path : NULL;
  }

  /**
   * What the results pages show above and below the results.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param string $query
   *   The query string that identifies the election and a person using a personal link.
   *
   * @return array
   *   header_text, footer_text, logo_url, logo_alt, has_logo (all empty when there is nothing to show).
   */
  public static function getDisplay($election, $query) {
    $logoId = (int) ($election->results_logo_file_id ?? 0);
    $hasLogo = $logoId && self::logoPath($logoId);
    return [
      'header_text' => self::decode($election->results_header_text ?? ''),
      'footer_text' => self::decode($election->results_footer_text ?? ''),
      'has_logo' => (bool) $hasLogo,
      'logo_url' => $hasLogo ? CRM_Utils_System::url('civicrm/elections/results-logo', $query . '&v=' . $logoId, FALSE, NULL, FALSE, TRUE) : '',
      'logo_alt' => self::decode($election->results_logo_alt ?? ''),
    ];
  }

  /**
   * The celebration to show now, if there is one: switched on and within its days of the results being published.
   *
   * @param CRM_Elections_BAO_Election $election
   * @param int|NULL $now
   *
   * @return array|NULL
   *   effect (never "surprise": that is already chosen), and the time it stops.
   */
  public static function getCelebration($election, $now = NULL) {
    if (empty($election->celebration_enabled) || empty($election->result_date) || empty($election->has_results_generated)) {
      return NULL;
    }
    $now = $now ?? time();
    $published = strtotime($election->result_date);
    $days = $election->celebration_days === NULL ? self::DAYS_DEFAULT : max(1, (int) $election->celebration_days);
    if (!$published || $now < $published || $now >= $published + $days * 86400) {
      return NULL;
    }
    return ['effect' => self::chooseEffect($election), 'until' => $published + $days * 86400];
  }

  /**
   * The effect for an election. "Surprise me" picks one from the election's id, so the page stays the same
   * every time it is opened.
   *
   * @param CRM_Elections_BAO_Election $election
   *
   * @return string
   */
  public static function chooseEffect($election) {
    $effect = $election->celebration_effect ?: self::EFFECT_CONFETTI;
    if ($effect === self::EFFECT_SURPRISE || !array_key_exists($effect, self::getEffects())) {
      $effects = array_keys(array_diff_key(self::getEffects(), [self::EFFECT_SURPRISE => 1]));
      return $effects[((int) $election->id) % count($effects)];
    }
    return $effect;
  }

  /**
   * The script that shows the celebration effects, with the form's preview and the effect list.
   */
  public static function addPageResources() {
    Civi::resources()
      ->addScriptFile(E::LONG_NAME, 'js/election-celebrate.js', 110, 'page-footer')
      ->addVars('electionsCelebrate', ['skip' => E::ts('Skip the celebration')]);
  }

  /**
   * Add the celebration to a results page when it is due, and what the page shows above and below the results.
   *
   * @param CRM_Core_Page $page
   * @param CRM_Elections_BAO_Election $election
   * @param string $query
   * @param bool $celebrate
   *   Whether this page celebrates (the results page does, the summary does not).
   */
  public static function assignToPage($page, $election, $query, $celebrate = TRUE) {
    $page->assign('resultsDisplay', self::getDisplay($election, $query));
    $celebration = $celebrate ? self::getCelebration($election) : NULL;
    if ($celebration) {
      self::addPageResources();
      Civi::resources()->addVars('electionsCelebrate', ['effect' => $celebration['effect'], 'auto' => TRUE]);
    }
  }

}
