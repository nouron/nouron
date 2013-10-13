@Techtree @Colony @Building
Feature: Invest construction points for a building
    To prepare a levelup for a building
    As a player
    I have to invest construction points (CP)

    Background:
        Given I am logged in
        And I have the required buildings

    Scenario Outline: Adding construction points
        Given I have spend <init> cp for the building
        And the building needs <need> cp
        When I spend <spend> cp
        Then <spend> cp should have been locked
        And the levelup cp progress shows <result> cp

        Examples:
            | init CP | spend CP | need CP | result CP |
            | 0 | 1 | 10 | 1 |
            | 3 | 1 | 10 | 4 |
            | 5 | 2 | 10 | 7 |
            | 5 | 5 | 10 | 10 |
            | 5 | 7 | 10 | 10 |

    Scenario: Not enough construction points
    	Given I have spend 0 cp for the building
    	And the building needs 10 cp
    	And I have 5 available cp left
    	When I spend 6 cp
    	Then I should see an error
