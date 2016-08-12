<?php

namespace ride\web\base\task;

use ride\library\i18n\translator\Translator;
use ride\library\form\FormBuilder;

/**
 * Abstract implementation to invoke a timely task
 */
abstract class AbstractTask implements Task {

    /**
     * Gets the display name of this task
     * @param \ride\library\i18n\translator\Translator $translator
     * @return string
     */
    public function getDisplayName(Translator $translator) {
        return $translator->translate('task.' . static::NAME);
    }

    /**
     * Hook to prepare the form to ask for extra arguments
     * @param \ride\library\form\FormBuilder $form
     * @param \ride\library\i18n\translator\Translator $translator
     * @return null
     */
    public function prepareForm(FormBuilder $form, Translator $translator) {

    }

    /**
     * Gets the result of this task
     * @param string $queueJobId Id of the invoked queue job
     * @return mixed
     */
    public function getResult($queueJobId) {
        return null;
    }

}
