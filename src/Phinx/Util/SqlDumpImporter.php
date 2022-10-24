<?php

namespace Phinx\Util;

use Phinx\Db\Adapter\AdapterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SqlDumpImporter
{

    public function __construct(
        private AdapterInterface $adapter,
        private OutputInterface $output
    ) {
    }

    public function import(string $file, int $max_statement_length = 2000000): void
    {
        $index = 0;
        foreach ($this->iterateSqlStatements($file, $max_statement_length) as $index => $statement) {
            try {
                $this->adapter->execute($statement);
            } catch (\Exception $e) {
                $this->output->writeln(sprintf('Failed importing %s - failing statement #%d:', $file, $index));
                $this->output->writeln($statement);
                throw $e;
            }

            if ($index % 20 === 0) {
                $this->output->write('.');
            }
        }

        if ($index === 0) {
            throw new \RuntimeException(sprintf("File %s is empty", $file));
        }

        // Close off the `.` progress line
        $this->output->write("\n");
        $this->output->writeln(sprintf('%s statements executed from %s', $index, $file));
    }

    private function iterateSqlStatements(string $file, int $max_statement_length)
    {
        $fp    = $this->openFileForReading($file);
        $index = 1;
        try {
            while ( ! feof($fp)) {
                $statement = stream_get_line($fp, $max_statement_length, ";\n");

                // stream_get_line does not return the delimiter so can't tell if it actually hit the max buffer size.
                // However it's fairly unlikely the statement is identical to the buffer size unless it was truncated.
                // If you hit this, either reduce the length of individual statements in the SQL file, or increase the
                // buffer size to accommodate them.
                if (strlen($statement) === $max_statement_length) {
                    throw new \RuntimeException(
                        sprintf(
                            "Statement %s was exactly the buffer size (%s bytes) - most likely it was truncated?\nGot:\n%s",
                            $index,
                            $max_statement_length,
                            $statement
                        )
                    );
                }

                if ($statement) {
                    yield $index => $statement;
                    $index++;
                }
            }
        } finally {
            fclose($fp);
        }
    }

    /**
     * @param string $file
     *
     * @return resource
     */
    private function openFileForReading(string $file)
    {
        try {
            \set_error_handler(fn(int $type, string $msg) => throw new \RuntimeException("fopen failed: $msg ($type)"));

            return fopen($file, 'r');
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('Could not open import file "%s": %s', $file, $e->getMessage()), 0, $e);
        } finally {
            \restore_error_handler();
        }
    }
}
