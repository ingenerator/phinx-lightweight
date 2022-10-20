<?php
/**
 * Phinx
 *
 * (The MIT license)
 * Copyright (c) 2017 Cake Software Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated * documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package    Phinx
 * @subpackage Phinx\Db\Adapter
 */
namespace Phinx\Db\Adapter;

use Phinx\Db\Table;
use Phinx\Db\Table\Column;
use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Index;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Wraps any adpter to record the time spend executing its commands
 */
class TimedOutputAdapter extends AdapterWrapper
{

    /**
     * {@inheritdoc}
     */
    public function getAdapterType()
    {
        return $this->getAdapter()->getAdapterType();
    }

    /**
     * Start timing a command.
     *
     * @return callable A function that is to be called when the command finishes
     */
    public function startCommandTimer()
    {
        $started = microtime(true);

        return function () use ($started) {
            $end = microtime(true);
            if (OutputInterface::VERBOSITY_VERBOSE <= $this->getOutput()->getVerbosity()) {
                $this->getOutput()->writeln('    -> ' . sprintf('%.4fs', $end - $started));
            }
        };
    }

    /**
     * Write a Phinx command to the output.
     *
     * @param string $command Command Name
     * @param array  $args    Command Args
     * @return void
     */
    public function writeCommand($command, $args = [])
    {
        if (OutputInterface::VERBOSITY_VERBOSE > $this->getOutput()->getVerbosity()) {
            return;
        }

        if (count($args)) {
            $outArr = [];
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    $arg = array_map(
                        function ($value) {
                            return '\'' . $value . '\'';
                        },
                        $arg
                    );
                    $outArr[] = '[' . implode(', ', $arg) . ']';
                    continue;
                }

                $outArr[] = '\'' . $arg . '\'';
            }
            $this->getOutput()->writeln(' -- ' . $command . '(' . implode(', ', $outArr) . ')');

            return;
        }

        $this->getOutput()->writeln(' -- ' . $command);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function insert(Table $table, $row)
    {
        $end = $this->startCommandTimer();
        $this->writeCommand('insert', [$table->getName()]);
        parent::insert($table, $row);
        $end();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function bulkinsert(Table $table, $rows)
    {
        $end = $this->startCommandTimer();
        $this->writeCommand('bulkinsert', [$table->getName()]);
        parent::bulkinsert($table, $rows);
        $end();
    }

}
