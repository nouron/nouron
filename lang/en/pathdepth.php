<?php

return [
    // ── Error messages (pathdepth.error_<code>, see PathDepthService::error)
    'error_unknown_entity' => 'No such family or character.',
    'error_not_met' => 'You have not met this character in this run yet.',
    'error_max_level' => 'The highest level has already been reached.',
    'error_building_level_cap' => 'The next level needs a higher building level.',
    'error_training_ap_cap' => 'No more than :cap AP can go into this training today. It continues tomorrow — or with another family.',
    'error_training_locked' => 'This training is still locked. The hangar needs a higher level.',
    'error_insufficient_ap' => 'Not enough AP available.',

    'error_conversation_ap_cap' => 'This character will not take more than :cap AP today. The conversation continues tomorrow — or talk to someone else.',
    'error_invalid_option' => 'No such conversation size.',
    'error_conversation_option_locked' => 'There is no room for a conversation this long. The cantina needs level :level for it.',
    'error_conversation_done_today' => 'You have already talked to this character today. Tomorrow again.',

    // ── Hangar: training
    'ui_training_title' => 'Training',
    'ui_experience_label' => 'Mission experience',
    'ui_level' => 'Level :level/:max',
    'ui_invest_progress' => ':spent/:needed AP',
    'ui_train' => 'Train',
    'ui_get_to_know' => 'Get to know',
    'ui_level_max' => 'Highest level',
    'ui_training_ap_today' => 'Today :spent/:cap AP already put into this training',
    'ui_training_ap_cap_tooltip' => 'This training takes only a limited amount of AP per Sol. It continues tomorrow — or with another family.',
    'ui_locked_building' => 'Locked: :building needs level :level.',
    'ui_training_effect' => 'Each level: +:pct% yield on quantity rewards',
    'ui_training_effect_now' => 'Now +:now% · next level +:next%',
    'ui_training_effect_max' => 'Now +:now% (highest level)',
    'ui_training_missions' => 'Applies to: :list',
    'ui_training_unlock' => 'From hangar level :level',

    // ── Cantina: nurture relationship
    'ui_relationship_title' => 'Nurture relationship',
    'ui_not_met' => 'Not met yet',
    'ui_relationship_run_hint' => 'Familiarity grows only within this run. What you learn about a character stays in the codex.',
    'ui_relationship_met_only' => 'Only characters you have already spoken to in this run are listed.',
    // Hint in the character dialog for characters not met yet.
    'ui_relationship_not_met_hint' => 'You have not exchanged a word yet. After your first conversation you can nurture the relationship here.',
    // Relationship level names 0–5 (owner 2026-11-11).
    'relationship_level_0' => 'stranger',
    'relationship_level_1' => 'newly met',
    'relationship_level_2' => 'acquainted',
    'relationship_level_3' => 'familiar',
    'relationship_level_4' => 'friends',
    'relationship_level_5' => 'like family',

    // ── Cantina: conversation options (T43-G4)
    'ui_conversation_cap_tooltip' => 'This character only has so much time for conversation per Sol. Tomorrow is another day — or talk to someone else.',
    'ui_conversation_today' => 'Today :spent/:cap AP',
    // Variant C (2026-11-13): one conversation per character and Sol; AP beyond the next
    // level are lost, with a warning.
    'conversation_done_today' => 'You have talked for today. Tomorrow again.',
    'conversation_wasted_chip' => ':ap AP · :n lost',
    'conversation_wasted_hint' => 'Only :n AP left to the next level – anything above that is lost.',
    'conversation_wasted_result' => ':n AP were lost.',
    // Tomas' advice: frequency by relationship level (game.bartender.advice_every_sols_by_relationship_level).
    'bartender_advice_every' => 'Advice every :n Sol',
    'bartender_advice_daily' => 'Advice every Sol',
    'bartender_advice_next' => 'Next advice in :n Sol',
    'bartender_advice_today' => 'Advice available today',
    // One name per option length is picked per Sol from these pools (any pool size works).
    // short = 1 AP, long = 2 AP (from cantina level 3).
    'conversation_names' => [
        'short' => ['Say a quick hello', 'Just nod', 'Exchange a word', 'Raise a glass', 'Pass by the table', 'Ask how it is going'],
        'long' => ['Take the evening', 'Talk over a drink', 'Stay until closing time', 'Share a table', 'Leave the shift behind', 'Pour another round'],
    ],

    // ── Mission families
    'family_exploration' => 'Exploration',
    'family_salvage' => 'Salvage',
    'family_supply' => 'Supply',
    'family_trade' => 'Trade',
    'family_protection' => 'Protection',

    // ── Cantina: line of a character for a locked conversation option (owner 2026-11-13).
    'option_locked_lines' => [
        'default' => [
            'You can hardly hear yourself think in here. Another time, when the cantina has more room.',
            'There is no room for a proper talk here. Expand the cantina, and I will sit down with you.',
            'Not here, not this cramped. Maybe once the cantina is bigger.',
        ],
        'bartender' => [
            'I have no room for more here — expand the cantina.',
            'Barely any elbow room behind this counter. More space, more time for you.',
            'Long evenings? I need a bigger cantina for that first.',
        ],
        'smuggler' => [
            'Too many ears in too little room. When the cantina grows, we talk properly.',
            '…not here. Everyone is breathing down everyone\'s neck. Give me a corner nobody can see into.',
            'What I have to say does not stay secret long with the next table listening. Build on.',
        ],
        'information_broker' => [
            'What I have to say needs a quiet corner. There is none here yet.',
            'A complete briefing requires a table without listeners. As soon as the cantina offers one, gladly.',
            'I do not discuss details between the door and the counter. Expand the cantina, and I will make the time.',
        ],
        'mechanic' => [
            'I am stuck between the door and the counter. Get me a table, then we talk.',
            'Long talks? Here? Might as well do it in a vent shaft — never mind. Expand first.',
            'Too cramped. Too loud. Once the cantina is bigger, I will explain everything. Maybe.',
        ],
        'corporate_rep' => [
            'A conversation of this length calls for appropriate facilities.',
            'I value our cooperation greatly. For more in-depth talks, however, an expanded cantina would be desirable.',
            'Good cooperation needs the right setting. This room does not quite offer it yet.',
        ],
        'founder' => [
            'My story is a long one. It needs more than this corner.',
            'Back then, five of us sat on crates and talked anyway. But for what I want to tell you, expand the cantina.',
            'Back then there was only a counter here. It has grown already. A little more, and I will sit down with you.',
        ],
        'gambler' => [
            'Nobody plays long at a crowded table. Build on, and I will stay seated.',
            'My big game needs a stage, Director, not a spot standing at the counter! Make the cantina bigger.',
            'Enough for a quick round. For the long night? That takes more room.',
        ],
        'ai_researcher' => [
            'Too loud, too cramped. My head needs more room than this.',
            'I wanted to tell you about the — no, wait, not here, I cannot think in here. When the cantina is bigger.',
            'Long thoughts need a quiet table. This cantina does not have one. Not yet.',
        ],
        'veteran' => [
            'No back to the wall, no clear view. I do not stay that long here.',
            'Short, yes. Long, no. Too cramped. Expand.',
            'Too many people, too little distance. Bigger cantina, then I stay.',
        ],
        'stranger' => [
            '…not here. Not like this.',
            '…more room. Then.',
            'This place is… still too small. For what there would be to say.',
        ],
        'doctor' => [
            'I barely have room to sit. For a long talk we need more space.',
            'I would like to listen to you longer. But in this crowd nobody truly listens. When the cantina grows.',
            'A good conversation needs rest, like a wound does. There is none to be found here yet.',
        ],
        'preacher' => [
            'Even the word needs a place where it can breathe.',
            'Every community grows with its rooms. When this cantina grows, so will our conversations.',
            'What needs saying does not bear a crowd. Give it a larger room.',
        ],
        'prospector' => [
            'More room, more stories. This cramped, I only tell the short ones.',
            'You do not explain the good sites standing up. Expand the cantina, and I will spread out the map.',
            'My bag does not even fit under the table here. Longer talks need more space.',
        ],
        'mercenary' => [
            'Short works. For anything else, this place is too cramped.',
            'I do not talk long where everyone listens in. Bigger cantina, then we talk.',
            'Give me a table with some distance. This cantina does not have one yet.',
        ],
        'scrap_dealer' => [
            'Everything is piled up in here. Make room, then we can talk longer.',
            'I would have so much to tell you — about valves, about seals, about everything! But I cannot even get my elbows apart in here. Expand!',
            'A long talk needs room for the samples, Director. More cantina, more Voss!',
        ],
    ],
];
