<?php

namespace Lpuygrenier\Lazykanban\Entity;

enum Status {
    case TODO;
    case IN_PROGRESS;
    case DONE;
}