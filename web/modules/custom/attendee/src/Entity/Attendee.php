<?php

namespace Drupal\attendee\Entity;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Definse the Attendee entity type.
 * 
 * @ContentEntityType(
 *  id = "attendee",
 *  lable  = @Translation("Atteendee"),
 *  handlers = {
 *      "form" = {
 *          "default"  = "Drupal\Core\Entity\ContentEntityForm",
 *          "add" = "Drupal\Core\Entity\ContentEntityForm",
 *          "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *          "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 * },
 *      "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 * },
 *  base_table = "attendee",
 *  data_table = "attendee_field_data",
 *  entity_keys = {
 *      "id" = "id",
 *      "uuid" = "uuid",
 * },
 *  links = {
 *      "canonical"  = "/attendee/{attendee}",
 *      "edit-form" = "/attendee/{attendee}/edit",
 *      "add-form" = "/attendee/{attendee}/add",
 *      "delete-form" = "/attendee/{attendee}/delete",
 *      "collection" = "/attendee/list",
 * },
 *  admin_premission = "administer attendees"
 * )
 */
class Attendee extends ContentEntityBase {

}