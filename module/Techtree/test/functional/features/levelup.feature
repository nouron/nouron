Feature: LevelUp a building
    For level up a building
    As a player
    The player has to fullfill all requirements

    Background:
        Given the player is logged in
        And the player has enough buildAP spend for the building
        And the requirements for the building are fullfilled

    Scenario: Not enough resources on colony
        Given the player has spend all needed buildAP for this building
        When the player pressed 'levelup'
        Then the colony resources check failed
        And the user gets an error message

    Scenario: Not enough Supply or Credits
        Given the player has spend all needed buildAP for this building
        When the player pressed 'levelup'
        Then the user resources check failed
        And the user gets an error message

    Scenario: successfull level up
        Given the player has spend enough BAP
        When the player pressed 'levelup'
        Then the building raised one level.

