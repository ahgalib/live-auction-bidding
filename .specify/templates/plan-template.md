# Implementation Plan: [FEATURE_NAME]

## 1. Context
- **Branch:** `feat/[name]`
- **Goal:** [One sentence summary]

## 2. Data Strategy
- **Redis Keys:** `prefix:{id}:field`
- **MySQL Tables:** [Table names]
- **GraphQL Schema:** [New Mutations/Subscriptions]

## 3. The Atomic Logic (Flow)
1. [Step 1: Lock]
2. [Step 2: Validate against Redis]
3. [Step 3: Update Redis & Extend Time]
4. [Step 4: Dispatch Async DB Job]

## 4. TDD Plan
- **Test A:** [Logic Test]
- **Test B:** [Concurrency/Race Test]
- **Test C:** [Edge Case/Boundary Test]