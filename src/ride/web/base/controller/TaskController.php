<?php

namespace ride\web\base\controller;

use ride\library\dependency\exception\DependencyException;
use ride\library\http\Response;
use ride\library\mvc\view\View;
use ride\library\queue\dispatcher\QueueDispatcher;
use ride\library\queue\QueueManager;
use ride\library\system\file\File;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\factory\ValidationFactory;

use ride\web\base\task\Task;

/**
 * Controller to select and invoke a timely task
 */
class TaskController extends AbstractController {

    /**
     * Action to select the task to invoke
     * @return null
     */
    public function selectAction() {
        $translator = $this->getTranslator();

        $taskOptions = array();

        $tasks = $this->dependencyInjector->getAll('ride\\web\\base\\task\\Task');
        foreach ($tasks as $taskId => $task) {
            $taskOptions[$taskId] = $task->getDisplayName($translator);
        }

        $form = $this->createFormBuilder();
        $form->addRow('task', 'option', array(
            'label' => $translator->translate('label.task'),
            'description' => $translator->translate('label.task.select.description'),
            'options' => $taskOptions,
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $url = $this->getUrl('admin.task.invoke', array(
                    'task' => $data['task'],
                ));

                $this->response->setRedirect($url);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('task/select', array(
            'form' => $form->getView(),
            'tasks' => $tasks,
        ));
    }

    /**
     * Action to ask extra task arguments before queueing the task for
     * invocation
     * @param string $task Id of the task
     * @return null
     */
    public function invokeAction(QueueDispatcher $queueDispatcher, $task) {
        $taskId = $task;

        try {
            $task = $this->dependencyInjector->get('ride\\web\\base\\task\\Task', $taskId);
        } catch (DependencyException $exception) {
            $this->response->setNotFound();

            return;
        }

        $translator = $this->getTranslator();
        $form = $this->createFormBuilder();

        $task->prepareForm($form, $translator);

        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $job = $task->getQueueJob($data);
                $jobStatus = $queueDispatcher->queue($job);

                $url = $this->getUrl('admin.task.progress', array(
                    'task' => $taskId,
                    'job' => $jobStatus->getId(),
                ));

                $this->response->setRedirect($url);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('task/invoke', array(
            'name' => $task->getDisplayName($translator),
            'task' => $task,
            'form' => $form->getView(),
        ));
    }

    /**
     * Action to show a progress indicator of the task
     * @param \ride\library\queue\QueueManager $queueManager
     * @param string $task Id of the task
     * @param string $job Id of the queue job
     * @return null
     */
    public function progressAction(QueueManager $queueManager, $task, $job) {
        $taskId = $task;

        try {
            $task = $this->dependencyInjector->get('ride\\web\\base\\task\\Task', $taskId);
        } catch (DependencyException $exception) {
            $this->response->setNotFound();

            return;
        }

        $job = $queueManager->getQueueJobStatus($job);
        if (!$job) {
            $this->response->setNotFound();

            return;
        }

        $this->setTemplateView('task/progress', array(
            'name' => $task->getDisplayName($this->getTranslator()),
            'taskId' => $taskId,
            'task' => $task,
            'queueJobStatus' => $job,
        ));
    }

    /**
     * Action to finish up an invokation of a task
     * @param \ride\library\queue\QueueManager $queueManager
     * @param string $task Id of the task
     * @param string $job Id of the queue job
     * @return null
          */
    public function finishAction(QueueManager $queueManager, ValidationFactory $validationFactory, $task, $job) {
        $taskId = $task;
        $jobId = $job;

        try {
            $task = $this->dependencyInjector->get('ride\\web\\base\\task\\Task', $taskId);
        } catch (DependencyException $exception) {
            $this->response->setNotFound();

            return;
        }

        $job = $queueManager->getQueueJobStatus($jobId);
        if ($job) {
            $this->response->setStatusCode(Response::STATUS_CODE_SERVER_ERROR);

            $this->setTemplateView('task/error', array(
                'name' => $task->getDisplayName($this->getTranslator()),
                'taskId' => $taskId,
                'task' => $task,
                'queueJobStatus' => $job,
            ));

            return;
        }

        $websiteValidator = $validationFactory->createValidator('website', array());

        $result = $task->getResult($jobId);
        if ($result instanceof File) {
            $this->setDownloadView($result, $result->getName(), true);
        } elseif ($result instanceof View) {
            $this->response->setView($result);
        } elseif ($websiteValidator->isValid($result)) {
            $this->response->setRedirect($result);
        } else {
            $this->setTemplateView('task/finish', array(
                'name' => $task->getDisplayName($this->getTranslator()),
                'taskId' => $taskId,
                'task' => $task,
                'result' => $result,
            ));
        }
    }

}
