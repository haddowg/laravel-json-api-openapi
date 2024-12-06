<?php

namespace haddowg\JsonApiOpenApi\Enums;

enum DefaultResponse: string
{
    case Deleting_204 = 'JsonApi:deletingOk';
    case Unauthorized_401 = 'JsonApi:unauthorized';
    case Creating_403 = 'JsonApi:creatingForbidden';
    case Updating_403 = 'JsonApi:updatingForbidden';
    case Forbidden_403 = 'JsonApi:forbidden';
    case NotFound_404 = 'JsonApi:notFound';
    case Creating_404 = 'JsonApi:creatingNotFound';
    case Deleting_404 = 'JsonApi:deletingNotFound';
    case Updating_404 = 'JsonApi:updatingNotFound';
    case ViewingOne_404 = 'JsonApi:viewingOneNotFound';
    case ViewingRelated_404 = 'JsonApi:viewingRelatedNotFound';
    case ViewingRelationship_404 = 'JsonApi:viewingRelationshipNotFound';
    case AttachingRelationship_204 = 'JsonApi:attachingRelationshipOk';
    case AttachingRelationship_403 = 'JsonApi:attachingRelationshipForbidden';
    case DetachingRelationship_204 = 'JsonApi:detachingRelationshipOk';
    case DetachingRelationship_403 = 'JsonApi:detachingRelationshipForbidden';
    case UpdatingRelationship_204 = 'JsonApi:updatingRelationshipOk';
    case UpdatingRelationship_403 = 'JsonApi:updatingRelationshipForbidden';
    case NotAcceptable_406 = 'JsonApi:notAcceptable';
    case Creating_409 = 'JsonApi:creatingConflict';
    case Updating_409 = 'JsonApi:updatingConflict';
    case UnsupportedMediaType_415 = 'JsonApi:unsupportedMediaType';
    case UnprocessableEntity_422 = 'JsonApi:unprocessableEntity';
    case TooManyRequests_429 = 'JsonApi:tooManyRequests';
    case BadRequest_4XX = 'JsonApi:badRequest';
    case ServerError_5XX = 'JsonApi:serverError';
}
