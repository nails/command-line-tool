<?php

namespace Nails\Cli\Command;

use Nails\Cli\Helper\Colors;
use Nails\Cli\Helper\Updates;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

abstract class Base extends Command
{
    /**
     * The success exit code
     *
     * @var int
     */
    const EXIT_CODE_SUCCESS = 0;

    // --------------------------------------------------------------------------

    /**
     * The console's input interface
     *
     * @var InputInterface
     */
    protected $oInput;

    /**
     * The console's output interface
     *
     * @var OutputInterface
     */
    protected $oOutput;

    /**
     * The question helper
     *
     * @var QuestionHelper
     */
    protected $oQuestion;

    // --------------------------------------------------------------------------

    /**
     * Execute the command
     *
     * @param InputInterface  $oInput
     * @param OutputInterface $oOutput
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        $this->oInput    = $oInput;
        $this->oOutput   = $oOutput;
        $this->oQuestion = $this->getHelper('question');

        Colors::setStyles($this->oOutput);

        if (Updates::check()) {
            $this->warning([
                sprintf(
                    'An update is available: %s (you have version %s)',
                    Updates::getLatestVersion(),
                    Updates::getCurrentVersion()
                ),
                'To update run: brew update && brew upgrade nails',
            ]);
        }

        return $this->go();
    }

    // --------------------------------------------------------------------------

    /**
     * The command's body
     *
     * @return mixed
     */
    abstract protected function go();

    // --------------------------------------------------------------------------

    /**
     * Display a underlined title banner
     *
     * @param string $sTitle The text to display
     */
    protected function banner(string $sTitle): self
    {
        $sTitle = $sTitle ? 'Nails CLI: ' . $sTitle : 'Nails CLI';
        $this->oOutput->writeln('');
        $this->oOutput->writeln('<info>' . $sTitle . '</info>');
        $this->oOutput->writeln('<info>' . str_repeat('-', strlen($sTitle)) . '</info>');
        $this->oOutput->writeln('');

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an error block
     *
     * @param string[] $aLines The lines to render
     */
    protected function error(array $aLines): self§
    {
        return $this->outputBlock($aLines, 'error');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an warning block
     *
     * @param string[] $aLines The lines to render
     */
    protected function warning(array $aLines): self
    {
        return $this->outputBlock($aLines, 'warning');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an coloured block
     *
     * @param array  $aLines The lines to render
     * @param string $sType  The type of block to render
     */
    protected function outputBlock(array $aLines, $sType): self
    {
        $aLengths   = array_map('strlen', $aLines);
        $iMaxLength = max($aLengths);

        $this->oOutput->writeln('<' . $sType . '> ' . str_pad('', $iMaxLength, ' ') . ' </' . $sType . '>');
        foreach ($aLines as $sLine) {
            $this->oOutput->writeln('<' . $sType . '> ' . str_pad($sLine, $iMaxLength, ' ') . ' </' . $sType . '>');
        }
        $this->oOutput->writeln('<' . $sType . '> ' . str_pad('', $iMaxLength, ' ') . ' </' . $sType . '>');

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Ask the user for input
     *
     * @param string   $sQuestion   The question to ask
     * @param string   $sDefault    The default response
     * @param callable $cValidation A validation callback
     *
     * @return mixed
     */
    protected function ask($sQuestion, $sDefault = null, $cValidation = null)
    {
        $sQuestion = $this->prepQuestion($sQuestion);
        if (!empty($sDefault)) {
            $sQuestion .= '[<comment>' . $sDefault . '</comment>] ';
        }

        $oQuestion = new Question($sQuestion, $sDefault);
        $sResponse = $this->oQuestion->ask($this->oInput, $this->oOutput, $oQuestion);

        if (is_callable($cValidation) && !call_user_func($cValidation, $sResponse)) {
            return $this->ask($sQuestion, $sDefault, $cValidation);
        }

        return $sResponse;
    }

    // --------------------------------------------------------------------------

    /**
     * Ask the user to select an option
     *
     * @param string   $sQuestion   The question to ask
     * @param array    $aOptions    An array of options
     * @param int      $iDefault    The default option
     * @param callable $cValidation A validation callback
     *
     * @return int
     */
    protected function choose(string $sQuestion, array $aOptions, int $iDefault = 0, $cValidation = null): int
    {
        $sQuestion = $this->prepQuestion($sQuestion);
        $oQuestion = new ChoiceQuestion($sQuestion, $aOptions, $iDefault);
        $sResponse = $this->oQuestion->ask($this->oInput, $this->oOutput, $oQuestion);

        if (is_callable($cValidation) && !call_user_func($cValidation, $sResponse)) {
            return $this->choose($sQuestion, $aOptions, $iDefault, $cValidation);
        }

        return array_search($sResponse, $aOptions);
    }

    // --------------------------------------------------------------------------

    /**
     * Ask the user for confirmation
     *
     * @param string $sQuestion the question to ask
     * @param bool   $bDefault  The default response
     */
    protected function confirm(string $sQuestion, bool $bDefault = true): bool
    {
        $sQuestion = $this->prepQuestion($sQuestion);
        $oQuestion = new ConfirmationQuestion($sQuestion, $bDefault);
        return $this->oQuestion->ask($this->oInput, $this->oOutput, $oQuestion);
    }

    // --------------------------------------------------------------------------

    /**
     * Prepare the question string
     *
     * @param string $sQuestion The question to prepare
     */
    private function prepQuestion(string $sQuestion): string
    {
        $sQuestion = trim($sQuestion);
        if (preg_match('/[^?:]$/', $sQuestion)) {
            $sQuestion .= '?';
        }
        $sQuestion .= ' ';
        return $sQuestion;
    }
}
