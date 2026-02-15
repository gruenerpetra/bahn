import unittest

from newmodule import add

class TestAdd(unittest.TestCase):
    def test1(self):
        self.assertEqual(add(2,4),6)
    
    def test2(self):
        self.assertEqual(add(0,0),0)

    def test3(self):
        self.assertEqual(add(2.3,2.6),4.9)

    def test4(self):
         self.assertEqual(add(2.3000,4.300),6.6)
    
    def test5(self):
        self.assertEqual(add('Hello',' World'), 'Hello World')


unittest.main()
        