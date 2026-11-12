<?php

namespace App\Installation;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class InstallationRecoveryRequired extends ConflictHttpException {}
