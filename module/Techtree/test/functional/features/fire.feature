@Techtree @Colony @Advisor
Feature: fire an advisor
    To regain spended Supply
    As a Player
    I have to fire an advisor

    Background:
        Given following advisors:
            | type | supply | credits | ap |
            | constructor | 2 | 5000 | 5 |
            | scientist | 2 | 5000 | 5 |
            | pilot | 2 | 10000 | 5 |

    Scenario Outline: successfull firing
        Given i have 5 constructors
        When i fire a constructor
        Then i will have 4 constructor
        And i gain 2 supply
        And i lose 5 available action points
        