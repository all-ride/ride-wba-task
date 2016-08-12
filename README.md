# Ride: Tasks web application

This module adds a UI to run different time-consuming tasks.

## Task

The _Task_ interface is what you need to implement for each task you want to offer.
 
You can inherit from _AbstractTask_ to omit the obvious functions.

### Define Your Task

To make the application know your task, add a dependency definition for it in your _dependencies.json_:

```json
{
    "dependencies": [
        {
            "interfaces": "ride\\web\\base\\task\\Task",
            "class": "my\\TestTask",
            "id": "test"
        }
    ]
}
```

## Code Sample

Check the following code sample to get you on your way:

```php
<?php

namespace my;

use ride\library\i18n\translator\Translator;
use ride\library\form\FormBuilder;
use ride\library\system\file\browser\FileBrowser;

use ride\web\base\task\AbstractTask;

class TestTask extends AbstractTask {

    /**
     * Name of this task, should be the same as your dependency id
     * @var string
     */
    const NAME = 'test';

    /**
     * Constructs a new test task
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser Let's 
     * use the file browser to retrieve the job result
     */
    public function __construct(FileBrowser $fileBrowser) {
        $this->fileBrowser = $fileBrowser;
    }

    /**
     * Hook to prepare the form to ask for extra arguments
     * @param \ride\library\form\FormBuilder $form
     * @param \ride\library\i18n\translator\Translator $translator
     * @return null
     */
    public function prepareForm(FormBuilder $form, Translator $translator) {
        // if you need extra information for your task, you can use this hook
        // to prepare a form which asks for these arguments
        $form->addRow('test', 'string', array(
            'validators' => array('required' => array()),
        ));
    }

    /**
     * Gets the queue job of this task
     * @param array $data Extra arguments from the form
     * @return \ride\library\queue\job\QueueJob Job to invoke
     */
    public function getQueueJob(array $data) {
        // your extra arguments, as defined in prepareForm, will be passed on to 
        // this method
        
        // you should return a QueueJob which holds the logic of your task
        return new TestQueueJob();
    }

    /**
     * Gets the result of this task
     * @param string $queueJobId Id of the invoked queue job
     * @return mixed
     */
    public function getResult($jobId) {
        // extract the result 
        $application = $this->fileBrowser->getApplicationDirectory();

        return $application->getChild('data/test-' . $jobId . '.txt');
    }

}
