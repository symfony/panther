<?php

declare(strict_types=1);

namespace Symfony\Component\Panther\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use UnexpectedValueException;
use ZipArchive;
use function fclose;
use function fopen;
use function fwrite;
use function preg_last_error_msg;
use function preg_match;
use function reset;
use function sprintf;
use const PHP_OS_FAMILY;

final class InstallChromeDriverCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    private const DRIVER_VERSION = 'driver-version';
    private const CHROME_BINARY = 'chrome-binary';
    private const DEFAULT_CHROME_BINARY = 'google-chrome';
    private const LATEST = 'latest';
    private const AUTO = 'auto';
    private const OS_FAMILY = 'os-family';
    private const WINDOWS = 'Windows';
    private const BSD = 'BSD';
    private const DARWIN = 'Darwin';
    private const SOLARIS = 'Solaris';
    private const LINUX = 'Linux';
    private const CHROMEDRIVER_API_URL = 'https://chromedriver.storage.googleapis.com';
    private const CHROMEDRIVER_API_VERSION_ENDPOINT = self::CHROMEDRIVER_API_URL . '/LATEST_RELEASE';
    private const BIN_DIRECTORY = __DIR__ . '/../../bin';
    private const DOWNLOAD_FILE_LOCATION = self::BIN_DIRECTORY . '/chromedriver.zip';
    private const CHROMEDRIVER_BINARY_LINUX = 'chromedriver_linux64';
    private const CHROMEDRIVER_BINARY_MAC = 'chromedriver_mac64';
    private const CHROMEDRIVER_BINARY_WINDOWS = 'chromedriver_win32';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ZipArchive
     */
    private $zip;

    public function __construct(
        string $name,
        ?HttpClientInterface $httpClient = null,
        ?Filesystem $filesystem = null,
        ?ZipArchive $zip = null
    )
    {
        parent::__construct($name);

        $this->httpClient = $httpClient ?? new NativeHttpClient();
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->zip = $zip ?? new ZipArchive();
    }

    protected function configure() : void
    {
        $this->setDescription('Installs chrome driver to run panther with Chrome');

        $this->addOption(
            self::DRIVER_VERSION,
            null,
            InputOption::VALUE_OPTIONAL,
            sprintf('The chrome driver version to install (x.x.x.x|%s|%s)', self::LATEST, self::AUTO),
            self::AUTO
        );

        $this->addOption(
            self::CHROME_BINARY,
            null,
            InputOption::VALUE_OPTIONAL,
            sprintf(
                'The chrome binary used to determine the correct chrome driver version when --%s=%s',
                self::DRIVER_VERSION,
                self::AUTO
            ),
            self::DEFAULT_CHROME_BINARY
        );

        $this->addOption(
            self::OS_FAMILY,
            null,
            InputOption::VALUE_OPTIONAL,
            sprintf(
                'The OS family used for installing the correct chrome driver binary (%s|%s|%s|%s|%s)',
                self::WINDOWS,
                self::BSD,
                self::DARWIN,
                self::SOLARIS,
                self::LINUX
            ),
            PHP_OS_FAMILY
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);

        $io->note('This command is experimental. Use at your own discretion.');

        $driverVersion = $input->getOption(self::DRIVER_VERSION);
        if ($driverVersion === self::AUTO) {
            try {
                $chromeVersion = $this->getInstalledChromeVersion($input->getOption(self::CHROME_BINARY));

                if ($io->isVerbose()) {
                    $io->writeln(sprintf('Chrome version "%s" found.', $chromeVersion));
                }

                $chromeDriverVersion = $this->getMatchingChromeDriverVersion($chromeVersion);
            } catch (Throwable $exception) {
                return $this->fail(
                    $io,
                    sprintf(
                        'Could not determine chrome version. Specify the chrome binary with "--%s" or manually specify a driver version with "--%s".',
                        self::CHROME_BINARY,
                        self::DRIVER_VERSION
                    ),
                    $exception
                );
            }
        } else if ($driverVersion === self::LATEST) {
            try {
                $chromeDriverVersion = $this->getLatestChromeDriverVersion();
            } catch (Throwable $exception) {
                return $this->fail($io, 'Unable to get the latest chrome version from API endpoint.', $exception);
            }
        } else {
            try {
                $chromeDriverVersion = $this->parseDriverVersion($driverVersion);
            } catch (Throwable $exception) {
                return $this->fail(
                    $io,
                    'Unable to parse provided driver version. Please format the driver version as: x.x.x.x',
                    $exception
                );
            }
        }

        if ($io->isVerbose()) {
            $io->writeln(sprintf('Downloading chrome driver version "%s".', $chromeDriverVersion));
        }

        try {
            $this->downloadChromeDriverZip(
                $chromeDriverVersion,
                $input->getOption(self::OS_FAMILY),
                $io->createProgressBar()
            );
            $io->writeln(' Download complete.');

        } catch (Throwable $exception) {
            return $this->fail(
                $io,
                sprintf('Unable to download chrome driver version "%s".', $chromeDriverVersion),
                $exception
            );
        }

        if ($io->isVerbose()) {
            $io->writeln('Extracting downloaded zip archive.');
        }

        try {
            $this->unzipChromeDriverArchive();
        } catch (Throwable $exception) {
            return $this->fail(
                $io,
                sprintf('Unable to unzip the downloaded file at "%s".', self::DOWNLOAD_FILE_LOCATION),
                $exception
            );
        }

        try {
            $this->removeChromeDriverArchive();
        } catch (Throwable $exception) {
            return $this->fail(
                $io,
                sprintf('Unable to remove the downloaded file at "%s".', self::DOWNLOAD_FILE_LOCATION),
                $exception
            );

        }

        $io->success(sprintf('Chrome driver %s successfully installed.', $chromeDriverVersion));

        return self::SUCCESS;
    }

    /**
     * @throws ProcessFailedException
     * @throws UnexpectedValueException
     * @throws RuntimeException
     */
    private function getInstalledChromeVersion(string $chromeBinary) : string
    {
        $process = Process::fromShellCommandline(sprintf('%s --version', $chromeBinary));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this->parseChromeVersion($process->getOutput());
    }

    private function parseChromeVersion(string $chromeVersion) : string
    {
        $success = preg_match('/\d+\.\d+\.\d+/', $chromeVersion, $output);

        if ($success === false) {
            throw new UnexpectedValueException(preg_last_error_msg());
        }

        if ($success === 0) {
            throw new RuntimeException(
                sprintf('Given chrome version "%s" could not be parsed.', $chromeVersion)
            );
        }

        return reset($output);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function getMatchingChromeDriverVersion(string $chromeVersion) : string
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('%s_%s', self::CHROMEDRIVER_API_VERSION_ENDPOINT, $chromeVersion)
        );

        return $this->parseDriverVersion($response->getContent());
    }

    /**
     * @throws UnexpectedValueException
     */
    private function parseDriverVersion(string $driverVersion) : string
    {
        $success = preg_match('/\d+\.\d+\.\d+\.\d+/', $driverVersion, $output);

        if ($success === false) {
            throw new UnexpectedValueException(preg_last_error_msg());
        }

        if ($success === 0) {
            throw new RuntimeException(
                sprintf('Given driver version "%s" could not be parsed.', $driverVersion)
            );
        }

        return reset($output);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function getLatestChromeDriverVersion() : string
    {
        $response = $this->httpClient->request('GET', self::CHROMEDRIVER_API_VERSION_ENDPOINT);

        return $this->parseDriverVersion($response->getContent());
    }

    private function getChromeDriverBinaryName(string $osFamily) : string
    {
        if ($osFamily === self::WINDOWS) {
            return self::CHROMEDRIVER_BINARY_WINDOWS;
        }

        if ($osFamily === self::DARWIN) {
            return self::CHROMEDRIVER_BINARY_MAC;
        }

        return self::CHROMEDRIVER_BINARY_LINUX;
    }

    /**
     * @throws RuntimeException
     * @throws TransportExceptionInterface
     */
    private function downloadChromeDriverZip(
        string $chromeDriverVersion,
        string $osFamily,
        ProgressBar $progressBar
    ) : void {
        if (!$this->filesystem->exists(self::BIN_DIRECTORY)) {
            $this->filesystem->mkdir(self::BIN_DIRECTORY);
        }

        $response = $this->httpClient->request(
            'GET',
            sprintf(
                '%s/%s/%s.zip',
                self::CHROMEDRIVER_API_URL,
                $chromeDriverVersion,
                $this->getChromeDriverBinaryName($osFamily)
            )
        );

        $fileHandler = fopen(self::DOWNLOAD_FILE_LOCATION, 'wb');
        if ($fileHandler === false) {
            throw new RuntimeException(sprintf('Could not open file handler for "%s".', self::DOWNLOAD_FILE_LOCATION));
        }

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($fileHandler, $chunk->getContent());
                $progressBar->advance();
            }
        } catch (TransportExceptionInterface $exception) {
            throw $exception;
        } finally {
            fclose($fileHandler);

            $progressBar->finish();
        }
    }

    /**
     * @throws RuntimeException
     */
    private function unzipChromeDriverArchive() : void
    {
        $success = $this->zip->open(self::DOWNLOAD_FILE_LOCATION);

        if ($success !== true) {
            throw new RuntimeException(sprintf('Could not open zip archive at "%s".', self::DOWNLOAD_FILE_LOCATION));
        }

        $success = $this->zip->extractTo(self::BIN_DIRECTORY);

        if ($success !== true) {
            throw new RuntimeException(
                sprintf(
                    'Could not extract zip archive at "%s" to "%s".',
                    self::DOWNLOAD_FILE_LOCATION,
                    self::BIN_DIRECTORY
                )
            );
        }

        $success = $this->zip->close();

        if ($success !== true) {
            throw new RuntimeException(sprintf('Could not close zip archive at "%s".', self::DOWNLOAD_FILE_LOCATION));
        }
    }

    private function removeChromeDriverArchive() : void
    {
        $this->filesystem->remove(self::DOWNLOAD_FILE_LOCATION);
    }

    private function fail($io, string $message, Throwable $exception) : int
    {
        $io->error($message);

        if ($io->isDebug()) {
            $io->writeln($exception->getMessage());
        }

        return self::FAILURE;
    }
}
