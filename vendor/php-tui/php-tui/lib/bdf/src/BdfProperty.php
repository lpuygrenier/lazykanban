<?php

declare(strict_types=1);

namespace PhpTui\BDF;

enum BdfProperty
{
    case ADD_STYLE_NAME;
    case AVERAGE_WIDTH;
    case AVG_CAPITAL_WIDTH;
    case AVG_LOWERCASE_WIDTH;
    case AXIS_LIMITS;
    case AXIS_NAMES;
    case AXIS_TYPES;
    case CAP_HEIGHT;
    case CHARSET_ENCODING;
    case CHARSET_REGISTRY;
    case COPYRIGHT;
    case DEFAULT_CHAR;
    case DESTINATION;
    case END_SPACE;
    case FACE_NAME;
    case FAMILY_NAME;
    case FIGURE_WIDTH;
    case FONT;
    case FONT_ASCENT;
    case FONT_DESCENT;
    case FONT_TYPE;
    case FONT_VERSION;
    case FOUNDRY;
    case FULL_NAME;
    case ITALIC_ANGLE;
    case MAX_SPACE;
    case MIN_SPACE;
    case NORM_SPACE;
    case NOTICE;
    case PIXEL_SIZE;
    case POINT_SIZE;
    case QUAD_WIDTH;
    case RASTERIZER_NAME;
    case RASTERIZER_VERSION;
    case RAW_ASCENT;
    case RAW_DESCENT;
    case RELATIVE_SETWIDTH;
    case RELATIVE_WEIGHT;
    case RESOLUTION;
    case RESOLUTION_X;
    case RESOLUTION_Y;
    case SETWIDTH_NAME;
    case SLANT;
    case SMALL_CAP_SIZE;
    case SPACING;
    case STRIKEOUT_ASCENT;
    case STRIKEOUT_DESCENT;
    case SUBSCRIPT_SIZE;
    case SUBSCRIPT_X;
    case SUBSCRIPT_Y;
    case SUPERSCRIPT_SIZE;
    case SUPERSCRIPT_X;
    case SUPERSCRIPT_Y;
    case UNDERLINE_POSITION;
    case UNDERLINE_THICKNESS;
    case WEIGHT;
    case WEIGHT_NAME;
    case X_HEIGHT;
}
