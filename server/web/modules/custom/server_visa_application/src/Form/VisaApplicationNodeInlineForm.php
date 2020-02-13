<?php

namespace Drupal\server_visa_application\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Form\NodeInlineForm;

/**
 * Node inline form handler.
 */
class VisaApplicationNodeInlineForm extends NodeInlineForm {

  /**
   * {@inheritdoc}
   */
  public function isTableDragEnabled($element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);

    if (isset($entity_form['field_address'])) {
      $entity_form['field_address']['widget'][0]['address']['#process'][] = [
        'Drupal\address\Element\Address',
        'processAddress',
      ];
      $entity_form['field_address']['widget'][0]['address']['#process'][] = 'server_visa_application_address_faux_required';
    }

    return $entity_form;
  }



}
