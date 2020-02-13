<?php

// @codingStandardsIgnoreFile

namespace Drupal\server_visa_application\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;

/**
 * Complex inline widget, for the Visa application.
 *
 * @FieldWidget(
 *   id = "visa_application_entity_form_complex",
 *   label = @Translation("Visa application Inline entity form - Complex"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class VisaApplicationInlineEntityFormComplex extends InlineEntityFormComplex {

  /**
   * Adds actions to the inline entity form.
   *
   * @param array $element
   *   Form array structure.
   *
   * @return array
   *   Renderable array.
   */
  public static function buildEntityFormActions($element) {
    $element = parent::buildEntityFormActions($element);

    $labels = [
      'companion' => t('Companion'),
    ];
    $type_label = $labels[$element['#bundle']] ?? NULL;

    // Build a delta suffix that's appended to button #name keys for uniqueness.
    if ($element['#op'] == 'add') {
      $save_label = $type_label ? t('Save @type', ['@type' => $type_label]) : $element['#bundle'];
    }
    elseif ($element['#op'] == 'duplicate') {
      $save_label = t('Duplicate @type_singular', ['@type_singular' => $element['#ief_labels']['singular']]);
    }
    else {
      $save_label = $type_label ? t('Update @type', ['@type' => $type_label]) : t('Update');
    }

    $element['actions']['ief_' . $element['#op'] . '_save']['#value'] = $save_label;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field_name = $items->getName();

    $helper_text = '';
    $type_label = '';
    $add_label = $this->t('Add');
    if ($field_name == 'field_companion') {
      $type_label = $this->t('Companion');
      $helper_text = '<p>' . $this->t('Please Enter up to 3 companions that will join you. You must enter at-least one. It might not make sense on a real Visa application, but for demonstration purposes it is great.') . '</p>';
      $helper_text .= '<div class="inline-helper">';
      $helper_text .= '<i class="fa fa-exclamation-circle" aria-hidden="true"></i>';
      $helper_text .= '<div class="inline-helper-text">' . $this->t('Your entries will not be saved until you click Save below.') . '</div>';
      $helper_text .= '</div>';
    }

    if ($type_label) {
      $add_label = $this->t('Add @type', ['@type' => $type_label]);
    }

    $element['actions']['ief_add']['#value'] = $add_label;
    $element['explanation'] = [
      '#weight' => 99,
      '#type' => 'container',
      '#attributes' => [
        'class' => ['explanation'],
      ],
      'message' => [
        '#markup' => $helper_text,
      ],
    ];

    if (isset($element['form'])) {
      $element['form']['#title'] = $type_label;
    }
    return $element;
  }

}
