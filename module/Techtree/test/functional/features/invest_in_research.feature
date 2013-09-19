@Techtree @Colony @Research
Feature: Invest construction points for a building or ship
    To construct a new building or ship
    As a player
    I have to invest construction points (CP)

    Background:
        Given I am logged in
        And I have enough CP available
        And I have the required buildings
        And I have the required researches

    Scenario Outline: Add multiple CP
        Given I have spend <init CP> for this technology
        And the technology needs <need CP>
        When I spend <spend CP>
        Then the technology should have <result CP>

        Examples:
            | init CP | spend CP | need CP | result CP |
            | 3 | 1 | 10 | 4 |
            | 5 | 2 | 10 | 7 |
            | 5 | 5 | 10 | 10 |
            | 5 | 7 | 10 | 10 |
