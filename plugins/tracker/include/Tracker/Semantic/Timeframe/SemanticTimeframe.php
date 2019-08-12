<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Timeframe;

use Codendi_Request;
use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;
use Tracker_Semantic;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\REST\SemanticTimeframeRepresentation;
use Tuleap\Tracker\Semantic\Timeframe\Administration\SemanticTimeframeAdministrationPresenterBuilder;

class SemanticTimeframe extends Tracker_Semantic
{
    public const NAME = 'timeframe';

    /**
     * @var Tracker_FormElement_Field_Date|null
     */
    private $start_date_field;
    /**
     * @var Tracker_FormElement_Field_Numeric|null
     */
    private $duration_field;

    /**
     * @var Tracker_FormElement_Field_Date|null
     */
    private $end_date_field;

    public function __construct(
        Tracker $tracker,
        ?Tracker_FormElement_Field_Date $start_date_field,
        ?Tracker_FormElement_Field_Numeric $duration_field,
        ?Tracker_FormElement_Field_Date $end_date_field
    ) {
        if ($duration_field !== null && $end_date_field !== null) {
            throw new TimeframeBrokenConfigurationException($tracker);
        }

        parent::__construct($tracker);
        $this->start_date_field = $start_date_field;
        $this->duration_field   = $duration_field;
        $this->end_date_field   = $end_date_field;
    }

    public function getShortName(): string
    {
        return self::NAME;
    }

    public function getLabel(): string
    {
        return dgettext('tuleap-tracker', 'Timeframe');
    }

    public function getDescription(): string
    {
        return dgettext('tuleap-tracker', 'Define the field to use to compute artifacts timeframes.');
    }

    public function display(): void
    {
        if (! $this->isDefined()) {
            echo dgettext('tuleap-tracker', 'This semantic is not defined yet.');
        } else {
            if ($this->duration_field !== null && $this->start_date_field !== null) {
                echo sprintf(
                    dgettext('tuleap-tracker', 'Timeframe is based on start date field "%s" and duration field "%s".'),
                    $this->start_date_field->getLabel(),
                    $this->duration_field->getLabel()
                );
            } elseif ($this->end_date_field !== null && $this->start_date_field !== null) {
                echo sprintf(
                    dgettext('tuleap-tracker', 'Timeframe is based on start date field "%s" and end date field "%s".'),
                    $this->start_date_field->getLabel(),
                    $this->end_date_field->getLabel()
                );
            }
        }
    }

    public function displayAdmin(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ): void {
        $sm->displaySemanticHeader($this, $tracker_manager);

        $builder = new SemanticTimeframeAdministrationPresenterBuilder(
            \Tracker_FormElementFactory::instance()
        );

        $renderer  = \TemplateRendererFactory::build()->getRenderer(
            __DIR__ . '/../../../../templates/timeframe-semantic'
        );
        $presenter = $builder->build(
            $this->getCSRFSynchronizerToken(),
            $this->tracker,
            $this->getUrl(),
            $this->start_date_field,
            $this->duration_field,
            $this->end_date_field
        );

        $renderer->renderToPage('timeframe-semantic-admin', $presenter);

        $js_assets = new IncludeAssets(
            __DIR__ . '/../../../../www/assets',
            TRACKER_BASE_URL . '/assets'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile(
            $js_assets->getFileURL("tracker-semantic-timeframe-option-selector.js")
        );

        $sm->displaySemanticFooter($this, $tracker_manager);
    }

    public function process(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ): void {
        if ($request->exist('update-semantic-timeframe')) {
            $this->getCSRFSynchronizerToken()->check();

            $timeframe_updator = new SemanticTimeframeUpdator(
                new SemanticTimeframeDao(),
                \Tracker_FormElementFactory::instance()
            );

            $timeframe_updator->update($this->tracker, $request);

            $this->redirectToSemanticTimeframeAdmin();
        } elseif ($request->exist('reset-semantic-timeframe')) {
            $this->getCSRFSynchronizerToken()->check();
            $this->resetSemantic();
            $this->redirectToSemanticTimeframeAdmin();
        }

        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }

    private function redirectToSemanticTimeframeAdmin()
    {
        $GLOBALS['Response']->redirect($this->getUrl());
    }

    public function exportToXml(SimpleXMLElement $root, $xmlMapping): void
    {
        if ($this->start_date_field === null || $this->duration_field === null) {
            return;
        }
        $start_date_field_id = $this->start_date_field->getId();
        $start_date_ref      = array_search($start_date_field_id, $xmlMapping);
        if (! $start_date_ref) {
            return;
        }
        $duration_field_id = $this->duration_field->getId();
        $duration_ref      = array_search($duration_field_id, $xmlMapping);
        if (! $duration_ref) {
            return;
        }

        $child = $root->addChild('semantic');
        $child->addAttribute('type', $this->getShortName());
        $child->addChild('start_date_field')->addAttribute('REF', $start_date_ref);
        $child->addChild('duration_field')->addAttribute('REF', $duration_ref);
    }

    public function isUsedInSemantics(Tracker_FormElement_Field $field): bool
    {
        return $this->isDurationField($field) || $this->isStartDateField($field) || $this->isEndDateField($field);
    }

    public function isEndDateField(Tracker_FormElement_Field $field): bool
    {
        return $this->end_date_field !== null &&
            (int)$field->getId() === (int)$this->end_date_field->getId();
    }

    public function isDurationField(Tracker_FormElement_Field $field): bool
    {
        return $this->duration_field !== null &&
            (int)$field->getId() === (int)$this->duration_field->getId();
    }

    public function isStartDateField(Tracker_FormElement_Field $field): bool
    {
        return $this->start_date_field !== null &&
            (int)$field->getId() === (int)$this->start_date_field->getId();
    }

    public function save(): bool
    {
        $dao   = new SemanticTimeframeDao();
        $saver = new SemanticTimeframeSaver($dao);

        return $saver->save($this);
    }

    public function getStartDateField(): ?Tracker_FormElement_Field_Date
    {
        return $this->start_date_field;
    }

    public function getDurationField(): ?Tracker_FormElement_Field_Numeric
    {
        return $this->duration_field;
    }

    public function getEndDateField(): ?Tracker_FormElement_Field_Date
    {
        return $this->end_date_field;
    }

    public function isDefined(): bool
    {
        return $this->start_date_field !== null &&
            ($this->duration_field !== null || $this->end_date_field !== null);
    }

    public function exportToREST(PFUser $user)
    {
        if ($this->start_date_field === null || $this->duration_field === null) {
            return null;
        }
        $start_date_field_id = (int) $this->start_date_field->getId();
        $duration_field_id   = (int) $this->duration_field->getId();

        $representation = new SemanticTimeframeRepresentation();
        $representation->build($start_date_field_id, $duration_field_id);

        return $representation;
    }

    private function getCSRFSynchronizerToken(): \CSRFSynchronizerToken
    {
        return new \CSRFSynchronizerToken(
            TRACKER_BASE_URL . "/?" . http_build_query(
                [
                    "semantic" => self::NAME,
                    "func"     => "admin-semantic"
                ]
            )
        );
    }

    private function resetSemantic() : void
    {
        (new SemanticTimeframeDao())->deleteTimeframeSemantic(
            (int) $this->tracker->getId()
        );

        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Semantic timeframe reset successfully')
        );
    }
}
