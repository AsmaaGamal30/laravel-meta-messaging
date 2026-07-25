<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Meta Messaging Error Hints
|--------------------------------------------------------------------------
|
| Every failure this package raises carries one of these lines as its hint.
| Publish them with
|
|     php artisan vendor:publish --tag=meta-messaging-lang
|
| to reword them for your team or translate them for another locale.
|
*/

return [

    /*
    | Configuration — raised before anything is sent.
    */

    'missing_credential' => 'The :key for the :channel account [:account] is not set. Add it under meta-messaging.accounts.:channel_key.:account, or set the matching environment variable.',
    'unknown_account' => 'There is no :channel account named [:account]. Configured accounts: :available.',
    'invalid_version' => 'Graph API version [:version] is not valid. Use the form v25.0 — a "v", a major number, a dot, and a minor number.',
    'invalid_login_type' => 'Instagram account [:account] has login_type [:value], which is not recognised. Use "instagram" for the Instagram Login flow (graph.instagram.com) or "facebook" for a Page-linked account (graph.facebook.com).',

    /*
    | Pre-flight validation — caught locally, so no API call is spent.
    */

    'unsupported_feature' => ':channel does not support :feature.',
    'unsupported_template' => ':channel does not support :feature. Templates available on :channel: :supported.',
    'empty_message' => 'There is nothing to send. Add text, an attachment, or a template before calling send().',
    'text_too_long' => 'The text is :length characters but :channel allows at most :limit. Shorten it or split it across messages.',
    'missing_recipient' => 'No recipient was set. Call ->to($id) with a page-scoped ID (Messenger) or an Instagram-scoped ID before sending.',
    'too_many_cards' => 'A generic template carries at most :limit cards, but :count were added.',
    'too_many_buttons' => 'A :context accepts at most :limit buttons, but :count were added.',
    'too_many_quick_replies' => 'A message carries at most :limit quick replies, but :count were added.',
    'quick_reply_title_too_long' => 'The quick reply title ":title" is :length characters; the limit is :limit.',
    'button_title_too_long' => 'The button title ":title" is :length characters; the limit is :limit.',
    'attachment_source_required' => 'An attachment needs a source. Pass a URL, a local file path, or a previously uploaded attachment ID.',
    'attachment_too_large' => 'The :type is :size, above the :limit ceiling Meta applies to :type attachments. Compress it or host a smaller version.',
    'local_file_unreadable' => 'The file [:path] does not exist or cannot be read.',
    'invalid_url' => 'The URL [:url] is not valid. Meta fetches attachments over the public internet, so it must be an absolute, publicly reachable https:// address.',
    'deprecated_tag' => 'Meta retired the :tag message tag on :date. Requests using it now fail with a bare "(#100) Invalid parameter", which does not say the tag is at fault. Instead, use :replacement.',
    'tag_requires_messaging_type' => 'A message tag only applies when messaging_type is MESSAGE_TAG. Either drop the tag or call ->messagingType(MessagingType::MessageTag).',
    'instagram_reaction_unsupported' => 'Instagram only accepts the ❤ reaction from businesses; [:emoji] was given. Meta rejects everything else.',
    'private_reply_text_only' => 'Private replies carry text only. Meta silently drops attachments, templates, and quick replies from them. Send the media as a follow-up once the person answers.',
    'reaction_requires_message_id' => 'Reacting needs the ID of the message to react to. Pass the "mid" from the messages webhook.',

    /*
    | Graph API failures — mapped from Meta's error codes.
    */

    'window_expired' => 'The 24 hour messaging window has closed. Meta only allows a free-form message within 24 hours of the person\'s last message to you. To reply later, use the HUMAN_AGENT tag (valid for 7 days, requires the Human Agent feature to be approved) or reach them through the Marketing Messages API.',
    'daily_limit_reached' => 'This conversation has hit Meta\'s daily message limit. Wait for the counter to reset, or reduce how many messages you send per person per day.',
    'recipient_unavailable' => 'This person cannot receive your message. They have blocked the :channel account, deleted the conversation, or set their privacy to refuse messages from businesses. Nothing in the request can be changed to make this succeed — treat it as permanent for this recipient.',
    'recipient_not_found' => 'No user matches recipient ID [:recipient]. Page-scoped IDs only work with the Page that issued them, and Instagram-scoped IDs only work with Instagram — check you are sending from the right account.',
    'app_in_development' => 'The app is still in development mode, so it can only message people with a role on it (admins, developers, testers). Add this person as a tester, or take the app live.',
    'private_reply_not_allowed' => 'This person\'s privacy settings do not allow private replies to their comments, or the Page has messaging turned off. You can still reply to the comment publicly with ->replyToComment().',
    'private_reply_already_sent' => 'A private reply was already sent for this comment. Meta allows exactly one per comment; the conversation can only continue once the person replies to it.',
    'invalid_comment' => 'Comment ID [:comment] is invalid, was deleted, or is not on content owned by this account. Private replies only work on comments left on your own posts, reels, or ads.',
    'private_reply_window_expired' => 'The private reply window has closed. Meta allows 7 days from when the comment was created, and for Instagram Live only during the broadcast itself.',
    'token_expired' => 'The access token has expired. Generate a new one — long-lived Page tokens last about 60 days, and User tokens far less.',
    'token_password_changed' => 'The access token stopped working because the account holder changed their password. They need to log in again so a fresh token can be issued.',
    'token_revoked' => 'The access token was revoked, either by the person removing the app or by an administrator. Re-authorise the app to obtain a new one.',
    'token_invalid' => 'The access token is malformed, belongs to a different app, or was never valid. Confirm you are using a Page access token for Messenger, or an Instagram User token for the Instagram Login flow.',
    'token_missing' => 'No access token reached Meta. Check the token configured for this account is a non-empty string.',
    'not_page_admin' => 'The token belongs to someone without an admin role on this Page. Issue the Page access token from an account that administers it.',
    'permission_denied' => 'The token is missing a permission this call needs — for this account that is usually :scope. Request it during login and have it approved for Advanced Access in the App Dashboard.',
    'rate_limited' => 'Meta is rate limiting this app or Page. Back off and retry; this error is transient. Check the X-App-Usage response header to see how close to the ceiling you are.',
    'temporarily_blocked' => 'Meta has temporarily blocked this app or Page, usually after repeated policy violations or an unusual traffic spike. Check the Alerts section of the App Dashboard for the specific reason.',
    'attachment_unfetchable' => 'Meta could not fetch the attachment. The URL must be publicly reachable over https without authentication, respond quickly, and serve a supported format within the size ceiling for its type.',
    'invalid_parameter' => 'Meta rejected a parameter in this request but did not say which one. The request payload is on the exception context. Common causes: a retired message tag, a template field that does not belong to this template type, or an ID from the wrong account.',
    'object_not_found' => 'Meta cannot find object [:object], or this token has no access to it. Confirm the ID is correct and the token belongs to the account that owns it.',
    'unknown_api_error' => 'Meta returned an error this package has no specific mapping for. Its own description is above; the request payload is on the exception context.',
    'malformed_response' => 'Meta returned a response that could not be read as JSON. This usually means an outage or a proxy in front of the request. The raw body is on the exception context.',
    'transport_failure' => 'The request never reached Meta: :reason. Check outbound network access and try again.',

];
