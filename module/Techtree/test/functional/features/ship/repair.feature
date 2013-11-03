@Techtree @Colony @Building @Ship
Feature: Repair a building or ship
    To repair a building or ship
    As a player
    I have to invest construction points (CP) to higher the status

    Background:
        Given I am logged in
        And I have enough CP available
        And I have the building or ship at least on level 1
        And the status ist lower than 100%

    Scenario Outline: Add multiple CP
        Given the status is <status>
        And the best status is <best status>
        When I spend <spend CP>
        Then the status should have <result CP>

        Examples:
            | status | spend CP | best status | result CP |
            | 3 | 1 | 10 | 4 |
            | 5 | 2 | 10 | 7 |
            | 5 | 5 | 10 | 10 |
            | 5 | 7 | 10 | 10 |
