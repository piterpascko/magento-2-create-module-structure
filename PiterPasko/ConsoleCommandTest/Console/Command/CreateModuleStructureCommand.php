<?php declare(strict_types=1);
namespace PiterPasko\ConsoleCommandTest\Console\Command;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\FileSystem\DriverPool;
use Magento\Framework\FileSystem\DirectoryList;
use Magento\Framework\Phrase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use PiterPasko\ConsoleCommandTest\Exception\InvalidModuleNameException;

class CreateModuleStructureCommand extends Command
{
    const MODULE_NAME_ARGUMENT = 'module_name';

    const MODULE_FOLDER = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';

    const MODULE_XML_TEMPLATE = self::MODULE_FOLDER . DIRECTORY_SEPARATOR . 'FileTemplates' . DIRECTORY_SEPARATOR . 'module.xml.template';

    const MODULE_XML_PATH = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'module.xml';

    const REGISTRATION_TEMPLATE = self::MODULE_FOLDER . DIRECTORY_SEPARATOR . 'FileTemplates' . DIRECTORY_SEPARATOR . 'registration.php.template';

    const REGISTRATION_PATH = DIRECTORY_SEPARATOR . 'registration.php';

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    public function __construct(DriverPool $driverPool, DirectoryList $directoryList, string $name = null)
    {
        parent::__construct($name);
        $this->fileDriver = $driverPool->getDriver(File::class);
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("module:structure:create")
            ->addArgument(
                self::MODULE_NAME_ARGUMENT,
                InputArgument::REQUIRED,
                'Name for generated module'
            )
            ->setDescription(
                "Creating module basic structure. Required argument 'Module_Name' which stands for 
                            VendorName_ModuleName separated using underscore"
            );
    }

    /**
     * {@inheritDoc}
     * @throws InvalidModuleNameException
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $moduleName = $input->getArgument(self::MODULE_NAME_ARGUMENT);
        if (!strpos($moduleName, '_')) {
            throw new InvalidModuleNameException(
                new Phrase('Invalid module name. Example of valid one: VendorName_ModuleName.')
            );
        }
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        list($vendorName, $moduleName) = explode('_', $input->getArgument(self::MODULE_NAME_ARGUMENT));
        if ($this->isModuleDirectoryExist($vendorName, $moduleName)) {
            $output->writeln(sprintf("Module %s_%s already exist!", $vendorName, $moduleName));
            return;
        }

        $moduleDir = $this->getModuleDir($vendorName, $moduleName);
        $this->fileDriver->createDirectory($moduleDir);
        $this->fileDriver->createDirectory($moduleDir . DIRECTORY_SEPARATOR . 'etc');

        $output->writeln("Necessary directories created!");

        $moduleXmlTemplate = $this->fileDriver->fileGetContents(self::MODULE_XML_TEMPLATE);

        $this->fileDriver->touch($moduleDir . self::MODULE_XML_PATH);
        $this->fileDriver->filePutContents($moduleDir . self::MODULE_XML_PATH, str_replace('{VendorName_ModuleName}', $vendorName . "_" . $moduleName, $moduleXmlTemplate));

        $registrationTemplate = $this->fileDriver->fileGetContents(self::REGISTRATION_TEMPLATE);

        $this->fileDriver->touch($moduleDir . self::REGISTRATION_PATH);
        $this->fileDriver->filePutContents(
            $moduleDir . self::REGISTRATION_PATH,
            str_replace('{VendorName_ModuleName}', $vendorName . "_" . $moduleName, $registrationTemplate)
        );

        $output->writeln("Necessary files created!");
        $output->writeln(sprintf("Module %s_%s successfully created!", $vendorName, $moduleName));
    }

    private function isModuleDirectoryExist($vendorName, $moduleName): bool
    {
        if ($this->fileDriver->isExists($this->getModuleDir($vendorName, $moduleName))) {
            return true;
        }
        return false;
    }

    private function getModuleDir($vendorName, $moduleName): string
    {
        $appDirectory = $this->directoryList->getPath('app');
        $moduleNameDir = $appDirectory . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . $vendorName . DIRECTORY_SEPARATOR . $moduleName;
        return $moduleNameDir;
    }
}
