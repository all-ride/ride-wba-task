<?php

namespace ride\web\base\task;

use ride\library\i18n\translator\Translator;
use ride\library\form\FormBuilder;

/**
 * Interface to invoke a timely task
 */
interface Task {

    /**
     * Gets the display name of this task
     * @param \ride\library\i18n\translator\Translator $translator
     * @return string
     */
    public function getDisplayName(Translator $translator);

    /**
     * Hook to prepare the form to ask for extra arguments
     * @param \ride\library\form\FormBuilder $form
     * @param \ride\library\i18n\translator\Translator $translator
     * @return null
     */
    public function prepareForm(FormBuilder $form, Translator $translator);

    /**
     * Gets the queue job of this task
     * @param array $data Extra arguments from the form
     * @return \ride\library\queue\job\QueueJob Job to invoke
     */
    public function getQueueJob(array $data);

    /**
     * Gets the result of this task
     * @param string $queueJobId Id of the invoked queue job
     * @return mixed
     */
    public function getResult($queueJobId);

}
