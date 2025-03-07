<?php

namespace FreerkMinnema\Sip;

enum Method: string
{
    case INVITE = 'INVITE';
    case ACK = 'ACK';
    case BYE = 'BYE';
    case CANCEL = 'CANCEL';
    case REGISTER = 'REGISTER';
    case OPTIONS = 'OPTIONS';
}
