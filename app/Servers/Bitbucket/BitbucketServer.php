<?php

namespace Deployer\Servers\Bitbucket;

use Deployer\Servers\Change;
use Deployer\Servers\Interfaces\ServerInterface;
use Deployer\Servers\Server;

/**
 * Class BitbucketServer
 *
 * @package Deployer\Servers\Bitbucket
 */
class BitbucketServer extends Server implements ServerInterface
{

    private $payload;

    public function __construct(array $repository)
    {
        $this->payload = $this->getPayload();
        $this->setBranches($repository['branches']);

        if (! is_null($this->payload) && isset($this->payload->repository)) {
            $this->setRepository($this->payload->repository->name);
            $this->setChanges($this->payload->push->changes);
        } else {
            throw new \Exception('Bitbucket Payload not found', 1101);
        }

        parent::__construct();
    }

    public function getPayload()
    {
        return json_decode(file_get_contents('php://input'));
    }

    /**
     * @param array $changes
     */
    public function setChanges(array $changes)
    {
        $changesClass = [];
        foreach ($changes as $changeRaw) {
            $changesClass[] = BitbucketFactory::newChange($changeRaw);
        }

        return parent::setChanges($changesClass);
    }

    public function beforeDeploymentTasks()
    {
        $this->log->info('Starting to deploy ' . $this->getRepository());
        foreach ($this->getChanges() as $change) {
            if ($change->isBranch() && array_key_exists($change->getBranch(), $this->getBranches())) {
                $this->addDeployableChange($change);
            } else {
                $this->log->warning(BitbucketMessages::getIngoredChange($change->getType(), $change->getBranch()));
            }
        }
    }

    public function afterDeploymentTasks()
    {
        if (! $this->log->inDebug()) {
            $this->log->info(BitbucketMessages::getDeploymentCompleted());

            foreach ($this->log->getMessages() as $message) {
                $message->print();
            }
        }
    }

    public function deploymentTasks()
    {
        foreach ($this->getDeployableChanges() as $change) {
            if ($this->deploy($change) && ! $this->log->hasAny('error')) {
                $this->log->success(BitbucketMessages::getDeploySuccess($change->getBranch()));
            } else {
                $this->log->error(BitbucketMessages::getDeployError($change->getBranch()));
            }

        }
    }

    /**
     * @param \Deployer\Servers\Change $change
     *
     * @return bool
     */
    public function deploy(Change $change)
    {
        $branch = $change->getBranch();
        $branchDir = $this->getBranchDir($branch);

        $this->log->info(BitbucketMessages::getDeployingBranch($branch));
        $cdCommand = 'cd ' . $branchDir;

        // We need to move to the directory in every command we execute
        foreach ($this->getBranchCommands($branch) as $pos => $command) {
            $output = [];
            $commandExec = str_replace(['%branch%', '%branchDir%'], [$branch, $branchDir], $command);

            $this->log->info("Executing " . $commandExec);
            exec($cdCommand . ' && ' . $commandExec, $output, $return);
            foreach ($output as $outputMessage) {
                $this->log->info($outputMessage);
            }
            if ($return !== 0) {
                $this->log->error('An error ' . $return . ' has occured while trying to execute ' . $commandExec);
                break;
            }
        }

        return true;
    }

    public function getBranchDir(string $branch)
    {
        return $this->getBranches()[$branch]['path'];
    }

    public function getBranchCommands(string $branch)
    {
        return $this->getBranches()[$branch]['commands'];
    }

}