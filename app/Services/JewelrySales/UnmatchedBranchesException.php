<?php

namespace App\Services\JewelrySales;

use InvalidArgumentException;

class UnmatchedBranchesException extends InvalidArgumentException
{
    /** @var array<int, string> */
    public readonly array $unmatchedBranches;

    /**
     * @param array<int, string> $unmatchedBranches
     */
    public function __construct(array $unmatchedBranches)
    {
        $this->unmatchedBranches = array_values($unmatchedBranches);

        parent::__construct(
            'Unmatched branch name(s): ' . implode(', ', $this->unmatchedBranches) . '. Please map them to existing branches before importing.'
        );
    }
}
