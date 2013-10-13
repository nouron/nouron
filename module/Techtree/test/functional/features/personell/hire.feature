@Techtree @Colony @Advisor
Feature: hire an advisor
    To gain action points
    As a Player
    I have to hire an advisor

    Scenario: successfull hiring
        Given I have enough "Credits"
        And I have enough free "Supply"
        When i hire an "advisor"
        Then credits are reduced
        And free Supply is bound
        And count of advisor increases by 1
        And action points increases by 5

    Scenario: not enough credits
        Given I have not enough Credits
        And I have enough free Supply
        When I hire an advisor
        Then credits check fails
        And I get an error message

    Scenario: not enough supply
        Given I have enough Credits
        And I have not enough free Supply
        When I hire an advisor
        Then supply check fails
        And I get an error message
