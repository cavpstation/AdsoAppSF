<?php

namespace Illuminate\Database;

use Illuminate\Support\Collection;

class DatabaseTransactionsManager
{
    /**
     * All of the committed transactions.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $committedTransactions;

    /**
     * All of the pending transactions.
     *
     * @var \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $pendingTransactions;

    /**
     * The current transaction.
     *
     * @var array<string, \Illuminate\Database\DatabaseTransactionRecord>
     */
    protected $currentTransaction = [];

    /** @var \Illuminate\Database\DatabaseTransactionRecord|null */
    protected $currentlyBeingExecutedTransaction = null;

    /**
     * Create a new database transactions manager instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->committedTransactions = new Collection;
        $this->pendingTransactions = new Collection;
    }

    /**
     * Start a new database transaction.
     *
     * @param  string  $connection
     * @param  int  $level
     * @return \Illuminate\Database\DatabaseTransactionRecord
     */
    public function begin($connection, $level)
    {
        $newTransaction = new DatabaseTransactionRecord(
            $connection,
            $level,
            $this->currentTransaction[$connection] ?? null
        );

        if (isset($this->currentTransaction[$connection])) {
            $this->currentTransaction[$connection]->addChild($newTransaction);
            $newTransaction->setParent($this->currentTransaction[$connection]);
        }

        $this->currentTransaction[$connection] = $newTransaction;
        $this->currentlyBeingExecutedTransaction = $newTransaction;

        return $newTransaction;
    }

    /**
     * Commit the root database transaction and execute callbacks.
     *
     * @param  string  $connection
     * @param  int  $levelBeingCommitted
     * @param  int  $newTransactionLevel
     * @return array
     */
    public function commit($connection, $levelBeingCommitted, $newTransactionLevel)
    {
//        $this->stageTransactions($connection, $levelBeingCommitted);
        $currentTransaction = $this->currentTransaction[$connection];

        if (isset($this->currentTransaction[$connection])) {
            $parentTransaction = $this->currentTransaction[$connection]->parent;
            $this->currentTransaction[$connection] = $parentTransaction;
            $this->currentlyBeingExecutedTransaction = $parentTransaction;
        }

        if (! $this->afterCommitCallbacksShouldBeExecuted($newTransactionLevel)) {
            return [];
        }

        $currentTransaction?->executeCallbacks();

        return;

        // This method is only called when the root database transaction is committed so there
        // shouldn't be any pending transactions, but going to clear them here anyways just
        // in case. This method could be refactored to receive a level in the future too.
        $this->pendingTransactions = $this->pendingTransactions->reject(
            fn ($transaction) => $transaction->connection === $connection
        )->values();

        [$forThisConnection, $forOtherConnections] = $this->committedTransactions->partition(
            fn ($transaction) => $transaction->connection == $connection
        );

        $this->committedTransactions = $forOtherConnections->values();

        $forThisConnection->map->executeCallbacks();

        return $forThisConnection;
    }

    /**
     * Move relevant pending transactions to a committed state.
     *
     * @param  string  $connection
     * @param  int  $levelBeingCommitted
     * @return void
     */
    public function stageTransactions($connection, $levelBeingCommitted)
    {
        $this->committedTransactions = $this->committedTransactions->merge(
            $this->pendingTransactions->filter(
                fn ($transaction) => $transaction->connection === $connection &&
                                     $transaction->level >= $levelBeingCommitted
            )
        );

        $this->pendingTransactions = $this->pendingTransactions->reject(
            fn ($transaction) => $transaction->connection === $connection &&
                                 $transaction->level >= $levelBeingCommitted
        );
    }

    /**
     * Rollback the active database transaction.
     *
     * @param  string  $connection
     * @param  int  $newTransactionLevel
     * @return void
     */
    public function rollback($connection, $newTransactionLevel)
    {
        if ($newTransactionLevel === 0) {
            $this->currentTransaction[$connection] = null;

            return;
        }

        $this->currentlyBeingExecutedTransaction->resetCallbacks();
        $this->currentlyBeingExecutedTransaction->resetChildren();

        $this->currentTransaction[$connection] = $this->currentTransaction[$connection]->parent;
        $this->currentlyBeingExecutedTransaction = $this->currentTransaction[$connection];
    }

    /**
     * Remove all pending, completed, and current transactions for the given connection name.
     *
     * @param  string  $connection
     * @return void
     */
    protected function removeAllTransactionsForConnection($connection)
    {
        $this->currentTransaction[$connection] = null;

        $this->pendingTransactions = $this->pendingTransactions->reject(
            fn ($transaction) => $transaction->connection == $connection
        )->values();

        $this->committedTransactions = $this->committedTransactions->reject(
            fn ($transaction) => $transaction->connection == $connection
        )->values();
    }

    /**
     * Remove all transactions that are children of the given transaction.
     *
     * @param  \Illuminate\Database\DatabaseTransactionRecord  $transaction
     * @return void
     */
    protected function removeCommittedTransactionsThatAreChildrenOf(DatabaseTransactionRecord $transaction)
    {
        [$removedTransactions, $this->committedTransactions] = $this->committedTransactions->partition(
            fn ($committed) => $committed->connection == $transaction->connection &&
                               $committed->parent === $transaction
        );

        // There may be multiple deeply nested transactions that have already committed that we
        // also need to remove. We will recurse down the children of all removed transaction
        // instances until there are no more deeply nested child transactions for removal.
        $removedTransactions->each(
            fn ($transaction) => $this->removeCommittedTransactionsThatAreChildrenOf($transaction)
        );
    }

    /**
     * Register a transaction callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public function addCallback($callback)
    {
        if ($current = $this->currentlyBeingExecutedTransaction) {
            return $current->addCallback($callback);
        }

        $callback();
    }

    /**
     * Get the transactions that are applicable to callbacks.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\DatabaseTransactionRecord>
     */
    public function callbackApplicableTransactions()
    {
        return $this->pendingTransactions;
    }

    /**
     * Determine if after commit callbacks should be executed for the given transaction level.
     *
     * @param  int  $level
     * @return bool
     */
    public function afterCommitCallbacksShouldBeExecuted($level)
    {
        return $level === 0;
    }

    /**
     * Get all of the pending transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPendingTransactions()
    {
        return $this->pendingTransactions;
    }

    /**
     * Get all of the committed transactions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCommittedTransactions()
    {
        return $this->committedTransactions;
    }
}
