<?php

/**
 * @file
 * Contains \Drupal\relaxed\Changes\Changes.
 */

namespace Drupal\relaxed\Changes;

use Drupal\multiversion\Entity\Index\SequenceIndex;

class Changes implements ChangesInterface {

  protected $lastSeq = 0;

  function __construct(SequenceIndex $sequenceIndex) {
    $this->sequenceIndex = $sequenceIndex;
  }

  /**
   * {@inheritdoc}
   */
  public function useWorkspace($name) {
    $this->workspaceName = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function lastSeq($seq) {
    $this->lastSeq = $seq;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNormal() {
    $changes = $this->sequenceIndex
      ->useWorkspace($this->workspaceName)
      ->getRange($this->lastSeq, NULL);

    // Format the result array.
    $result = array();
    $seq = 0;
    foreach ($changes as $change) {
      if (isset($change['local']) && $change['local'] == TRUE) {
        continue;
      }
      $change_result = array(
        'changes' => array(
          array('rev' => $change['rev']),
        ),
        'id' => $change['entity_uuid'],
        'seq' => $seq,
      );
      $seq++;
      if ($change['deleted']) {
        $change_result['deleted'] = TRUE;
      }
      $result['results'][] = $change_result;
    }
    $last_seq_number = count($result['results']);
    if ($last_seq_number > 0) {
      $result['last_seq'] = $last_seq_number - 1;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongpoll() {
    $no_change = TRUE;
    do {
      // @todo Implement exponential wait time.
      $change = $this->sequenceIndex
        ->useWorkspace($this->workspaceName)
        ->getRange($this->lastSeq, NULL);
      $no_change = empty($change) ? TRUE : FALSE;
    } while ($no_change);
    // Format longpoll change to API spec.
    return $change;
  }

}
