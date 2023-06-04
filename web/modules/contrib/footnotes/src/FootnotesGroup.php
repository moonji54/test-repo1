<?php

namespace Drupal\footnotes;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Store grouping of all footnotes.
 */
class FootnotesGroup implements TrustedCallbackInterface {

  /**
   * The footer markup.
   *
   * @var string
   */
  protected $matches = [];

  /**
   * Add additional footnotes to the group.
   *
   * @param array $matches
   *   The additional footnotes to add.
   */
  public function add(array $matches) {
    if (count($this->matches) > 0) {
      for ($i = count($matches) - 1; $i >= 0; $i--) {
        $existing_footnote = NULL;
        $j = 0;
        while (is_null($existing_footnote) && $j < count($this->matches)) {
          if ($this->matches[$j]['fn_id'] == $matches[$i]['fn_id']) {
            $existing_footnote = $matches[$i];
          }
          $j++;
        }
        if (!is_null($existing_footnote)) {
          $j--;
          $this->matches[$j]['ref_id'] = is_array($this->matches[$j]['ref_id']) ? $this->matches[$j]['ref_id'] : [$this->matches[$j]['ref_id']];
          $existing_footnote['ref_id'] = is_array($existing_footnote['ref_id']) ? $existing_footnote['ref_id'] : [$existing_footnote['ref_id']];
          $this->matches[$j]['ref_id'] = array_values(array_unique(array_merge($this->matches[$j]['ref_id'], $existing_footnote['ref_id'])));
          array_splice($matches, $i, 1);
        }
      }
    }
    $this->matches = array_merge($this->matches, $matches);
  }

  /**
   * Build the footnotes footer render array.
   *
   * @return array
   *   A render array for the footer containing all matches.
   */
  public function buildFooter() {
    return [
      '#theme' => 'footnote_list',
      '#footnotes' => $this->matches,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['buildFooter'];
  }

}
