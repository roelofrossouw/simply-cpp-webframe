<?php

    namespace BS;

    enum PadSize: string
    {
        case None = '0';
        case Pad1 = '1';
        case Pad2 = '2';
        case Pad3 = '3';
        case Pad4 = '4';
        case Pad5 = '5';
        case Automatic = 'auto';
    }