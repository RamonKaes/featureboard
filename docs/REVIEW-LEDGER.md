# Review ledger

Findings from reviews that were deliberately not fixed. A later review does not report them again, unless it has a new concrete scenario or the reason below no longer holds.

The first review ran on commit `bf053a1`, and the decisions date from 2026-11-04.

| Finding                                                           | Decision         | Reason                                                                                                                               | Reconsider when                           |
| ----------------------------------------------------------------- | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------- |
| M6, part: reset and registration reveal whether an account exists | Keep             | Hiding it on one form is pointless while registration has to refuse a taken address. Doing it properly needs email verification      | Email verification is added               |
| M6, part: a stranger's reset request invalidates an earlier link  | Keep             | Needs someone to know the address, the broker limits it to once per minute, and the owner can request a new link                     | Accounts become valuable                  |
| M10: votes stay when the author rewrites a new request            | Product decision | The brief allows editing in status `new`. An "edited" mark needs its own column, because `updated_at` also changes with the status   | Abuse is observed, or the brief changes   |
| M13, part: no Content Security Policy                             | Keep             | The inline theme script needs nonces, and that cost has no benefit without a deployment. The other headers are set                   | The app is deployed                       |
| M15: an expired session loses typed text                          | Keep             | A real fix needs drafts stored in the browser, and that is a feature of its own                                                      | Users report lost text                    |
| M16, part: the user lock in `DeleteUser` has no test              | Keep             | Proving it takes a slow timed test for a rare race on the own account. The behaviour was measured with `FOR KEY SHARE` in the review | The action changes                        |
| M16, part: 63 mutation timeouts are not decided one by one        | Keep             | The mutation run does not name them, and the score already counts them                                                               | Mutation testing becomes a gate           |
| L2: login from the vote button ends on the start page             | Keep             | Small benefit for a demo. It needs the target carried through the login                                                              | Guests matter more                        |
| L3, part: suggestions stay when the title exceeds 120 characters  | Keep             | Harmless, and the field shows its limit                                                                                              | Never on its own                          |
| L4: unused kit code                                               | Keep             | No effect on behaviour, and removing it makes later kit updates harder to compare                                                    | The kit is no longer followed             |
| L5: lookalike names such as "Admin" and invisible characters      | Keep             | Needs a name policy, and admins carry no visible badge anyway                                                                        | Moderation is added                       |
| L8: roadmap without a limit                                       | Keep             | Only admins grow the `done` column, and the demo stays small                                                                         | A product collects many finished requests |

Open question for the author, not a code decision:

- L6: `composer.json` says `"license": "MIT"` without a `LICENSE` file. Choosing a licence is the author's call.
